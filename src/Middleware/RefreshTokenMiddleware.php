<?php
/**
 * Description: 无感刷新token,单一设备登录
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 16:15
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Cyd622\LaravelApi\Jobs\SaveUserTokenJob;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class RefreshTokenMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     * @param $request
     * @param Closure $next
     * @param null $guard
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        // Check token and throws exception
        $this->checkForToken($request);

        // Get default guard
        $presentGuard = $guard ?? Auth::getDefaultDriver();

        $token = $this->auth->getToken()->get();

        $authGuard = $this->auth->getClaim('guard');

        if (!$authGuard || $authGuard != $presentGuard) {
            throw new TokenInvalidException('auth guard invalid');
        }

        try {

            if ($user = auth($authGuard)->authenticate()) {
                $request->guard = $authGuard;
                return $next($request);
            }

            throw new UnauthorizedHttpException('jwt-auth', '未登录');

        } catch (TokenExpiredException $exception) {
            // Catch token expired exception. so, we use try/catch refresh new token and add the request headers.
            try {

                $token = $this->auth->refresh();
                // Use once login to ensure the success of this request
                Auth::guard($authGuard)->onceUsingId($this->auth->getClaim('sub'));

                // Save user token in job
                $user = Auth::guard($authGuard)->user();
                SaveUserTokenJob::dispatch($user, $token, $authGuard);

            } catch (JWTException $exception) {
                // All token not used. need re-login
                throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
            }
        }
        // Add token to request header
        return $this->setAuthenticationHeader($next($request), $token);
    }
}