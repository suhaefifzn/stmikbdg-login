<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MyWebService;

class AuthService extends MyWebService
{
    use HasFactory;

    public function __construct() {
        parent::__construct('authentications');
    }

    public function login(string $email, string $password) {
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        return $this->post($payload, '?platform=web');
    }

    public function validateUserSiteAccess($siteURL) {
        return $this->get(null, '/check/site?url=' . $siteURL);
    }

    public function logout() {
        return $this->delete();
    }
}
