<?php
/**
 * 后台权限中间件
 * Class AdminAuth
 * @package app\http\middleware
 */

namespace huangsen\auth\middleware;

use huangsen\auth\Auth;
use think\exception\HttpException;
use traits\controller\Jump;

/**
 * 权限验证中间件
 * Class AdminAuth
 * @package huangsen\auth\middleware
 */
class AdminAuth
{
    use Jump;

    public function handle($request, \Closure $next)
    {
        //注入用户
        app()->auth = Auth::instance();
        app()->user = Auth::instance()->getUserInfo();

        if (!Auth::instance()->notNeedLogin()) {
            Auth::instance()->isLogin() || $this->redirect('public/login');
            Auth::instance()->check() || $this->error('您无权限操作');
        }

        return $next($request);
    }
}
