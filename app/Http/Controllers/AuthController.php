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
        $siteDst = filter_var($request->query('site'));

        if (!$siteDst) {
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
            return redirect()->away($siteDst . '?token=' . Session::get('token'));
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

    public function redirectToDestination(Request $request) {
        $siteDst = filter_var($request->query('site'), FILTER_VALIDATE_URL);


        if ($siteDst) {
            $validateAccess = $this->service->validateUserSiteAccess($siteDst);
            $statusValidate = $validateAccess->getData('data')['status'];

            if ($statusValidate === 'fail') {
                return view('auth.forbidden', [
                    'message' => $validateAccess->getData('data')['message'],
                ]);
            }
        } else {
            return 'Alamat web yang akan diakses setelah login tidak ditemukan';
        }

        return redirect()->away($siteDst . '?token=' . Session::get('token'));
    }
}
