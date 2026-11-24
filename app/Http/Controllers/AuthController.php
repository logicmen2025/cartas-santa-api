<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** POST /api/auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $sesion = $this->auth->iniciarSesion(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        return response()->json(['data' => $sesion]);
    }
}
