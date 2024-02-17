<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// * Services
use App\Models\AuthService;

class AuthController extends Controller
{
    protected $service;

    public function __construct() {
        $this->service = new AuthService();    
    }

    public function index() {
        return view('auth.index');
    }

    public function authenticate(Request $request) {
        return $this->service->login($request->email, $request->password);
    }

    public function logout() {
        return $this->service->logout();
    }
}