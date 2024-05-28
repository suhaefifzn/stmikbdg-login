<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// * Services
use App\Models\AuthService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    protected $service;

    public function __construct() {
        $this->service = new AuthService();
    }

    public function index(Request $request) {
        $siteDst = filter_var($request->query('site'), FILTER_VALIDATE_URL);

        if (!$siteDst) {
            /**
             * Jika user langsung mengakses alamat loginnya saja, maka tidak ada site destination
             * Akan tetapi, bisa saja user telah login dan memiliki token sebelumnya
             * Maka cek token, jika valid tampilkan daftar web yang bisa diaksesnya
             */
            if (request()->cookie('user_token') !== null) {
                $validateAccess = $this->service->validateUserSiteAccess($siteDst);
                $statusValidate = $validateAccess->getData('data')['status'];

                if ($statusValidate === 'success') {
                    return view('contents.authenticated', [
                        'message' => 'Autentikasi berhasil. Berikut adalah beberapa situs yang dapat Anda akses.',
                        'urls' => $validateAccess->getData('data')['data']['site'],
                    ]);
                }
            }

            return view('contents.login', [
                'error' => '404',
                'message' => 'Alamat web yang akan Anda akses setelah login tidak ditemukan.'
            ]);
        }

        if (request()->cookie('user_token') !== null) {
            return redirect('/verify?site=' . $siteDst);
        }

        return view('auth.index'); // halaman login
    }

    public function authenticate(Request $request) {
        // rate limiter - batasi percobaan login
        $throttleKey = 'signin' . $request->email;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'status' => 'fail',
                'message' => 'Terlalu banyak percobaan login.<br/>Coba lagi dalam ' . $seconds . ' detik',
            ], 429);
        }

        // login ke api
        $login = $this->service->login($request->email, $request->password);

        if($login->getData('data')['status'] === 'success') {
            // simpan token dan roles user sebagai cookies
            $accessToken = $login->getData('data')['data']['token']['access_token'];
            $userRoles = $login->getData('data')['data']['roles'];
            $cookieToken = cookie('user_token', $accessToken, 355);
            $cookieRoles = cookie('user_roles', serialize($userRoles), 355);

            return $login->cookie($cookieToken)->cookie($cookieRoles);
        }

        // login failed
        RateLimiter::hit($throttleKey);

        return $login;
    }

    public function logout(Request $request) {
        $siteDstPayload = filter_var($request->site, FILTER_VALIDATE_URL);
        $siteDstQuery = filter_var($request->query('site'), FILTER_VALIDATE_URL);

        if (!$siteDstPayload || !$siteDstQuery) {
            return view('contents.logout', [
                'error' => '404',
                'message' => 'Alamat web asal tidak ditemukan.'
            ]);
        }

        $this->service->logout();

        $cookieToken = Cookie::forget('user_token');
        $cookieRoles = Cookie::forget('user_roles');

        return redirect('login?site=' . $siteDstPayload ? $siteDstPayload : $siteDstQuery)
            ->withCookie($cookieToken)->withCookie($cookieRoles);
    }

    public function verifyUserSiteAccess(Request $request) {
        $siteDst = filter_var($request->query('site'), FILTER_VALIDATE_URL);
        $hasAccess = self::hasSiteAccess($siteDst);

        if (!filter_var($hasAccess, FILTER_VALIDATE_BOOLEAN)) {
            return $hasAccess;
        }

        if (!$request->query('role')) {
            $getUserRoles = self::getUserRoles($siteDst);

            if ($getUserRoles['roles']->count() === 1) {
                return self::redirectToSiteDst($siteDst, $getUserRoles['roles']->keys()[0]);
            }

            return view('contents.roles', [
                'site' => $getUserRoles['site'],
                'roles' => $getUserRoles['roles']->keys(),
                'message' => 'Anda memiliki beberapa role aktif. Silahkan pilih role yang sesuai untuk mengakses '
                    . $getUserRoles['site']['name'] . '.',
            ]);
        }

        return self::redirectToSiteDst($siteDst, $request->query('role'));
    }

    public function redirectToSiteDst($siteDst = null, $role = null) {
        if ($siteDst) {
            $hasAccess = self::hasSiteAccess($siteDst);

            if (!filter_var($hasAccess, FILTER_VALIDATE_BOOLEAN)) {
                return $hasAccess;
            }
        } else {
            return view('contents.login', [
                'error' => '404',
                'message' => 'Alamat web yang akan Anda akses setelah login tidak ditemukan.'
            ]);
        }

        return redirect()->away(
            $siteDst . '?token=' . request()->cookie('user_token') . '&role=' . $role
        );
    }

    public function requestOTPByEmail(Request $request) {
        $request->validate([
            'email' => 'string|email'
        ]);

        return $this->service->verifyEmailForgotPassword($request->email);
    }

    public function confirmResetPasswordByOTP(Request $request) {
        $request->validate([
            'otp' => 'string|min:6|max:6',
            'new_password' => 'string|min:8|max:64|regex:/^\S*$/u',
            'confirm_password' => 'string|same:new_password'
        ]);

        return $this->service->resetPasswordByOTP(
            $request->otp, $request->new_password, $request->confirm_password
        );
    }

    private function hasSiteAccess($site) {
        $validateAccess = $this->service->validateUserSiteAccess($site);
        $statusCode = $validateAccess->getStatusCode();
        $siteDetail = $this->service->getSiteInfo($site)->getData('data')['data']['site'];

        if ($statusCode == 403) {
            return view('contents.forbidden', [
                'error' => '403',
                'message' => 'Oops. Maaf, sepertinya Anda tidak memiliki hak akses ke ' . $siteDetail['name'],
                'site' => $siteDetail,
            ]);
        } else if ($statusCode == 401) {
            return redirect('/logout?site=' . $site);
        }

        return true;
    }

    private function getUserRoles($site) {
        // check role user
        $roles = collect(unserialize(request()->cookie('user_roles')));
        $userRoles = $roles->filter(function ($value) {
            return $value === true;
        })->toArray();

        // check role site
        $site = $this->service->getSiteInfo($site)->getData('data')['data']['site'];
        $data = [
            'site' => [
                'url' => $site['url'],
                'name' => $site['name']
            ],
            'roles' => collect(array_intersect_assoc($userRoles, $site))
        ];

        return $data;
    }

}
