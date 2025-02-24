<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CustomAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::parseToken()->getToken();
            $user = JWTAuth::parseToken()->authenticate();
            print_r($user);die;
            \Log::info('Parsed Token:', ['token' => $token]);

            $user = $token->authenticate();
            \Log::info('Authenticated User:', ['user' => $user]);

            if (!$user) {
                \Log::warning('User not found');
                return response()->json(['error' => 'User not found'], 404);
            }

            $request->setUser($user);

            return $next($request);
        } catch (JWTException $e) {
            \Log::error('JWTException:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token not provided or invalid. Please authenticate using a Bearer token.'], 401);
        } catch (\Exception $e) {
            \Log::error('Exception:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while authenticating the user.'], 500);
        }
    }

}
