<?php
// +----------------------------------------------------------------------
// | ThinkPHP 5 [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 .
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
namespace huangsen\auth;

use think\facade\Cache;
use think\facade\Config;
use think\Db;
use think\facade\Session;
use think\facade\Request;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth = \huangsen\auth\Auth::getInstance();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth = \huangsen\auth\Auth::getInstance();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(prefix_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(prefix_auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在prefix_auth_rule 表中定义一条规则时，[如果type为1]， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */
class Auth
{
    //默认配置
    protected $config = array(
        'auth_on' => true, // 权限开关
        'auth_cache' => true, //是否开启缓存
        'auth_key' => '_auth_', // 数据缓存的key
        'auth_rule' => 'auth_rule', // 权限规则表
        'role' => 'auth_role', // 角色表
        'role_user' => 'auth_role_user', // 用户角色对应表
        'users' => 'users', // 用户信息表
        'users_auth_fields' => '',//用户需要验证的规则表达式字段 空代表所有用户字段
        //不需要登录的
        'no_need_login_url' => [
            '/public/login'
        ],
        //登录用户不需要验证的
        'allow_visit' => [
            '/file/upload'
        ],
    );

    //用户信息
    protected $userInfo = [];

    //对象实例
    protected static $instance;

    //权限检查模式
    protected $model = 1;

    /**
     * 单列
     * @param array $options 参数
     * @return object|static 对象
     */
    public static function getInstance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }


    /**
     * 类架构函数 （私有构造函数，防止外界实例化对象）
     * @param array $options 参数
     * Auth constructor.
     */
    private function __construct($options = [])
    {
        //可设置配置项 auth, 此配置项为数组。 thinkphp5.1 需要配置auth.php
        if ($auth = Config::get('auth.')) {
            $this->config = array_merge($this->config, $auth);
        }

        // 将传递过来的参数替换
        if (!empty($options) && is_array($options)) {
            $this->config = array_merge($this->config, $options);
        }
    }

    /**
     * 私有克隆函数，防止外办克隆对象
     */
    private function __clone()
    {
    }

    /**
     * 检查权限
     * @param array $name 需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param int $uid 用户基本信息
     * @param string $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return bool 通过验证返回true;失败返回false
     * @author: Huang
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check($name = [], $uid = null, $relation = 'or')
    {
        is_null($name) && $name = $this->getPath();
        is_null($uid) && $uid = $this->getUserId();

        // 是否开启权限开关 (或者 用户id为 1  超级管理员)
        if (empty($this->config['auth_on']) || $uid == 1) {
            return true;
        }

        //不需要验证权限路径
        if (in_array($name, $this->config['allow_visit'])) {
            return true;
        }

        if (empty($uid) || empty($name)) {
            return false;
        }

        //获取用户对应角色
        $groups = $this->getRoleUser($uid);

        if (empty($groups)) {
            return false;
        }

        $groups = array_column($groups, 'role_id');

        if (empty($groups)) {
            return false;
        }

        //如果用户角色有超级管理员直接验证成功
        if (in_array(1, $groups)) {
            return true;
        }

        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        } elseif (is_numeric($name)) {
            $name = [$name];
            $this->model = 2;
        }

        // 获取该用户 对应 该规则名的权限列表
        $rules = $this->getRuleData($uid);

        if (empty($rules)) {
            return false;
        }

        //当前规则是否存有权限
        $lsit = [];
        foreach ($rules as $rule) {
            if (in_array($rule, $name)) {
                $lsit[] = $rule;
            }
        }

        if ($relation == 'or' && !empty($lsit)) {
            return true;
        }
        $diff = array_diff($name, $lsit);
        if ($relation == 'and' && empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户拥有权限的规则数据的id
     * @param $uid 用户id
     * @return array|bool|mixed|\PDOStatement|string|\think\Collection
     * @author: Huang
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRuleData($uid)
    {

        $rule_data = $this->config['auth_cache'] ? Cache::get($this->getRuleKey($uid)) : [];

        if (empty($rule_data) || !is_array($rule_data)) {

            //读取用户所属用户组
            $groups = $this->getRoleUser($uid);

            foreach ($groups as $g) {
                if (!empty($g['rules'])) {
                    $rule_data = array_merge($rule_data, explode(',', trim($g['rules'], ',')));
                }
            }

            if (empty($rule_data)) return false;

            $rule_data = array_unique($rule_data);

            $map['id'] = $rule_data;

            $map['status'] = 1;

            $rule_data = Db::name($this->config['auth_rule'])
                ->where($map)
                ->field('id,condition,name')
                ->select();

            if (empty($rule_data)) return false;

            //获取用户信息,一维数组
            $user = $this->getUserInfoById($uid);

            //获取判断权限的模式
            $name = $this->model == 1 ? 'name' : 'id';

            $list = [];
            //根据condition进行验证
            foreach ($rule_data as $rule) {
                if (!empty($rule['condition'])) {

                    $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);

                    @(eval('$condition=(' . $command . ');'));

                    if ($condition) {
                        $list[] = $rule[$name];
                    }
                } else {
//                        $list[] = strtolower($rule[$name]);
                    $list[] = $rule[$name];
                }
            }

            if ($this->config['auth_cache'])
                Cache::set($this->getRuleKey($uid), $list);

        }

        return $list;

    }

    /**
     * 获取权限缓存标识
     * @param $uid 用户id
     * @return string
     * @author: Huang
     */
    private function getRuleKey($uid)
    {
        return $this->getKey('rule_data_list' . $uid);
    }

    /**
     * 获取用户拥有角色数据
     * @param $uid 用户id
     * @return array|bool|mixed 拥有的角色数组
     * @author: Huang
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoleUser($uid = null)
    {
        is_null($uid) && $uid = $this->getUserId();

        $role_user_data = $this->config['auth_cache'] ? Session::get($this->getRoleUserKey()) : [];

        if (empty($role_user_data) || !is_array($role_user_data)) {

            $role_user_data = Db::name($this->config['role_user'] . ' a')
                ->where("a.user_id='$uid' and g.status=1")
                ->join($this->config['role'] . " g", "a.role_id=g.id")
                ->field('user_id,role_id,name,rules')->select();

            if (empty($role_user_data)) return false;

            if ($this->config['auth_cache'])
                Session::set($this->getRoleUserKey(), $role_user_data);
        }

        return $role_user_data;

    }

    /**
     * 获取用户角色 session key
     * @return string
     */
    private function getRoleUserKey()
    {
        return $this->getKey('role_user_list');
    }

    /**
     * 获取auth 的session key
     * @param $key 需要获取的key
     * @return string
     */
    private function getKey($key)
    {
        return md5($this->config['auth_key'] . $key);
    }

    /**
     * 根据用户id登录
     * @param null $admin
     * @return bool
     * @author: Huang
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login($admin = null)
    {
        if (is_numeric($admin)) {
            $admin = model($this->config['users'])->find($admin);
            unset($admin['password']);
        }
        if ($admin) {
            session('gold-admin', $admin);
            return true;
        }
        return false;
    }

    /**
     * 退出登陆
     * @return bool
     * @author: Huang
     */
    public function logout()
    {
        session('gold-admin', null);
        $this->userInfo = null;
        return true;
    }

    /**
     * 检查是否登录
     * @return bool
     * @author: Huang
     */
    public function isLogin()
    {
        return !empty($this->getUserInfo());
    }

    /**
     * 当前登录用户
     * @return mixed
     * @author: Huang
     */
    public function getUserInfo()
    {
        $this->userInfo = !empty($this->userInfo) ? $this->userInfo : session('gold-admin');
        return $this->userInfo;
    }

    /**
     * 获取登陆了用户id
     * @return null
     * @author: Huang
     */
    public function getUserId()
    {
        $user = $this->getUserInfo();
        return $user ? $user->id : null;
    }

    /**
     * 根据角色id获取全部用户id
     * @param  int $role_id 用户组id
     * @return array        用户数组所有用户ids
     */
    public function getUserIdByRoleId($role_id = null)
    {

        $where['role_id'] = $role_id;
        $user_ids = Db::name($this->config['role_user'])
            ->where($where)
            ->value('user_id');
        return $user_ids;
    }

    /**
     * 获取path
     * @return string
     * @author: Huang
     */
    public function getPath()
    {
        return '/' . str_replace('.', '/', strtolower(Request::module() . '/' . Request::controller() . '/' . Request::action()));
    }

    /**
     * 根据用户id获取用户信息
     * @param $uid 用户id
     * @return array|mixed|null|\PDOStatement|string|\think\Model
     * @author: Huang
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfoById($uid = null)
    {
        is_null($uid) && $uid = $this->getUserId();

        $userinfo = $this->config['auth_cache'] ? Session::get($this->getUserKey($uid)) : [];

        if (empty($userinfo) || !is_array($userinfo)) {
            $user = Db::name($this->config['users']);
            // 获取用户表主键
            $_pk = is_string($user->getPk()) ? $user->getPk() : 'id';

            $userinfo = $user->field($this->config['users_auth_fields'])->where($_pk, $uid)->find();

            if ($this->config['auth_cache'])
                Session::set($this->getUserKey($uid), $userinfo);
        }
        return $userinfo;
    }

    /**
     * 获取用户信息的session key
     * @param $uid 用户id
     * @return string
     */
    private function getUserKey($uid)
    {
        return $this->getKey('user_info' . $uid);
    }

    /**
     * 校验url，是否需要用户验证
     * @return bool
     * @author: Huang
     */
    public function notNeedLogin()
    {
        $urls = $this->config['no_need_login_url'];
        if (in_array($this->getPath(), $urls)) {
            return true;
        }
        return false;
    }
}