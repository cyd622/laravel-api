<?php
/**
 * Description: 登录组件
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 16:37
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Cyd622\LaravelApi\Jobs\SaveUserTokenJob;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

trait LoginActionTrait
{

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username()
    {
        return 'mobile';
    }

    /**
     * Get the login password to be used by the controller.
     *
     * @return string
     */
    protected function password()
    {
        return 'password';
    }

    /**
     * Get the needed authorization credentials from the request.
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), $this->password());
    }

    /**
     * Get user last token
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user
     * @param $guard
     * @return string|null
     */
    protected function getUserLastToken($user, $guard)
    {
        $uid = $user->getKey();
        // User.api.1:LastToken
        $key = sprintf("User.%s-%s:LastToken", $guard, $uid);
        return Cache::get($key);
    }

    /**
     * Handle a login request to the application.
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        return $this->authenticateClient($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function authenticateClient(Request $request)
    {
        $presentGuard = $request->get('guard', Auth::getDefaultDriver());

        $credentials = $this->credentials($request);

        // add guard sign to payload.
        $token = Auth::guard($presentGuard)->claims(['guard' => $presentGuard])->attempt($credentials);

        if ($token) {

            $user = Auth::guard($presentGuard)->user();

            $lastToken = $this->getUserLastToken($user, $presentGuard);

            if ($lastToken) {
                try {
                    JWTAuth::setToken($lastToken)->invalidate();
                } catch (TokenExpiredException $e) {
                    // Because an exception will be thrown if an expired token is
                    // invalidated again, we catch the exception without any processing.
                }
            }

            SaveUserTokenJob::dispatch($user, $token, $presentGuard);
            return $this->success($user, ['token_type' => 'Bearer', 'access_token' => $token,]);
        }

        return $this->error('账号或密码错误', 400);
    }

    /**
     * Log the user out of the application.
     * @return mixed
     */
    public function logout()
    {
        Auth::logout();
        return $this->message('退出登录成功');
    }
}