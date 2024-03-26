<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// * Services
use App\Models\AuthService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

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
            if (Session::exists('token')) {
                $validateAccess = $this->service->validateUserSiteAccess($siteDst);
                $statusValidate = $validateAccess->getData('data')['status'];

                if ($statusValidate === 'success') {
                    return view('auth.authenticated', [
                        'message' => 'Verifikasi berhasil. Berikut adalah beberapa situs yang dapat Anda akses:',
                        'urls' => $validateAccess->getData('data')['data']['site'],
                    ]);
                }
            }

            return 'Alamat web yang akan diakses setelah login tidak ditemukan';
        }

        if (Session::exists('token')) {
            return redirect('/verify?site=' . $siteDst);
        }

        return view('auth.index');
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
            Session::put('roles', $login->getData('data')['data']['roles']);
            return $login;
        }

        // login failed
        RateLimiter::hit($throttleKey);

        return $login;
    }

    public function logout(Request $request) {
        $siteDstPayload = filter_var($request->site, FILTER_VALIDATE_URL);
        $siteDstQuery = filter_var($request->query('site'), FILTER_VALIDATE_URL);

        if (!$siteDstPayload || !$siteDstQuery) {
            return 'Alamat web asal tidak ditemukan';
        }

        $this->service->logout();

        return redirect('login?site=' . $siteDstPayload ? $siteDstPayload : $siteDstQuery);
    }

    public function verifyUserSiteAccess(Request $request) {
        $siteDst = filter_var($request->query('site'), FILTER_VALIDATE_URL);
        $hasAccess = self::hasSiteAccess($siteDst);

        if (!filter_var($hasAccess, FILTER_VALIDATE_BOOLEAN)) {
            return $hasAccess;
        }

        if (!$request->query('role')) {
            $getUserRoles = self::getUserRoles();

            if ($getUserRoles->count() === 1) {
                return self::redirectToSiteDst($siteDst, $getUserRoles->keys()[0]);
            }

            return view('auth.roles', [
                'site' => $siteDst,
                'roles' => $getUserRoles->keys(),
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
            return 'Alamat web yang akan diakses setelah login tidak ditemukan';
        }

        return redirect()->away($siteDst . '?token=' . Session::get('token') . '&role=' . $role);
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

        if ($statusCode == 403) {
            return view('auth.forbidden', [
                'message' => $validateAccess->getData('data')['message'],
            ]);
        } else if ($statusCode == 401) {
            return redirect('/logout?site=' . $site);
        }

        return true;
    }

    private function getUserRoles() {
        $roles = collect(Session::get('roles'));
        $activeRoles = $roles->filter(function ($value) {
            return $value === true;
        });

        return $activeRoles;
    }

}
