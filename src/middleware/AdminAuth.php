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
        app()->auth = Auth::getInstance();
        app()->user = Auth::getInstance()->getUserInfo();

        if (!app()->auth->notNeedLogin()) {
            app()->auth->isLogin() || $this->redirect(app()->auth->getConif('login_path'));
            app()->auth->check() || $this->error('未授权访问');
        }

        return $next($request);
    }
}
