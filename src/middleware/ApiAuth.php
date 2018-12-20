<?php
/**
 * Created by PhpStorm.
 * User: HuangSen
 * Date: 2018/12/20
 * Time: 16:55
 * @author: Huang
 */

namespace huangsen\auth\middleware;

use huangsen\auth\Token;
use think\exception\HttpException;
use Firebase\JWT\JWT;

/**
 * Class ApiAuth
 * Api 验证中间件
 * @package huangsen\auth\middleware
 */
class ApiAuth
{
    public function handle($request, \Closure $next)
    {
        //验证登录
        $token = Token::instance();
        $jwt = substr($request->header('Authorization'), 7);

        $user = null;
        try {
            $jwt = (array)JWT::decode($jwt, env('APP_SECRET'), ['HS256']);
            if ($jwt && $jwt['exp'] > time()) {
                $user = $token->user($jwt);
            }
        } catch (\Exception $e) {
            $jwt = null;
        }

        //注入用户
        app()->auth = $token;
        app()->user = $user;

        //检查访问权限
        if (!$token->checkPublicUrl()) {
            if (empty($jwt)) {
                throw new HttpException(401, '未授权访问');
            }

            if (!$user) {
                throw new HttpException(401, '登录已过期，请重新登录');
            }
            if ($user['token'] != $jwt['token']) {
                throw new HttpException(401, '用户验证失败，请重新登录');
            }
        }

        return $next($request);
    }
}