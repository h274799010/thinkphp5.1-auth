

## 安装
> composer require huangsen/think5.1-auth:dev-master

## 配置
### 公共配置
```
// auth配置 auth.php 配置文件
'auth'  => [
    'auth_on' => true, // 权限开关
        'auth_cache' => false, //是否开启缓存
        'auth_key' => '_auth_', // 数据缓存的key
        'auth_rule' => 'auth_rule', // 权限规则表
        'role' => 'auth_role', // 角色表
        'role_user' => 'auth_role_user', // 用户角色对应表
        'users' => 'user', // 用户信息表
        'users_auth_fields' => '',//用户需要验证的规则表达式字段 空代表所有用户字段

        //不需要登录的
        'no_need_login_url' => [
            '/public/login'
        ],

        //登录用户不需要验证的
        'allow_visit' => [
            '/file/upload'
        ]
],
```

### 导入数据表
> `gold_` 为自定义的数据表前缀

```
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for gold_auth_role
-- ----------------------------
DROP TABLE IF EXISTS `gold_auth_role`;
CREATE TABLE `gold_auth_role`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '角色名称',
  `pid` smallint(6) NULL DEFAULT NULL COMMENT '父角色ID',
  `mark` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '角色唯一标识',
  `rules` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '用户组拥有的规则id，多个规则 , 隔开',
  `status` tinyint(1) UNSIGNED NULL DEFAULT NULL COMMENT '用户组状态：为1正常，为0禁用,-1为删除',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `pid` int(2) UNSIGNED NULL DEFAULT NULL COMMENT '父级ID',
  `listorder` int(3) NOT NULL DEFAULT 0 COMMENT '排序，优先级，越小优先级越高',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `parentId`(`pid`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for gold_auth_role_user
-- ----------------------------
DROP TABLE IF EXISTS `gold_auth_role_user`;
CREATE TABLE `gold_auth_role_user`  (
  `role_id` int(11) UNSIGNED NULL DEFAULT 0 COMMENT '角色 id',
  `user_id` int(11) NULL DEFAULT 0 COMMENT '用户id',
  INDEX `group_id`(`role_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户角色对应表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for gold_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `gold_auth_rule`;
CREATE TABLE `gold_auth_rule`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则编号',
  `name` char(80) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '链接地址 模块/控制器/方法',
  `title` char(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '规则中文名称',
  `type` tinyint(1) NULL DEFAULT 1 COMMENT '规则类型1pc端规则，2手机端权限 ，3 同时是手机和pc',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：为1正常，为0禁用',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '规则描述',
  `condition` char(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证',
  `sort` int(10) NULL DEFAULT 0 COMMENT '排序，优先级，越小优先级越高',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT 0 COMMENT '更新时间',
  `pid` int(2) UNSIGNED NULL DEFAULT NULL COMMENT '父级ID',
  `fontawesome` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'fontawesome的图标',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '规则表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
```

## 原理
Auth权限认证是按规则进行认证。
在数据库中我们有

- 规则表（gold_auth_rule）
- 角色表(gold_role)
- 用户角色对应表（gold_role_user）
- 用户表（gold_users）用户表需要自己创建

在规则表中定义规则,角色表里面定角色和,权限授权表里面对应角色拥有的权限(一个角色对应多个权限),在用户角色对应表里面定义用户有多少角色(多个)


## 使用
判断权限方法
```

// 获取auth实例
$auth = \huangsen\auth\Auth::getInstance();

// 检测权限
if($auth->check('规则1,规则2','用户id','and')){// 第一个参数是规则名称(也可以是规则id参数必须为数字),第二个参数是用户UID,第三个参数为判断条件
	//有显示操作按钮的权限
}else{
	//没有显示操作按钮的权限
}
```

Auth类也可以对节点进行认证，我们只要将规则名称，定义为节点名称就行了。
可以在公共控制器Base中定义_initialize方法
```
<?php
use think\Controller;
use think\auth\Auth;
class Base extends Controller
{
    public function _initialize()
	{
		$controller = request()->controller();
		$action = request()->action();
		$auth = \huangsen\auth\Auth::getInstance();
		if(!$auth->check($controller . '-' . $action, session('uid'))){
			$this->error('你没有权限访问');
		}
    }
 }
```
这时候我们可以在数据库中添加的节点规则， 格式为： “控制器名称-方法名称”

Auth 类 还可以多个规则一起认证 如：
```
$auth->check('rule1,rule2',uid);
```
表示 认证用户只要有rule1的权限或rule2的权限，只要有一个规则的权限，认证返回结果就为true 即认证通过。 默认多个权限的关系是 “or” 关系，也就是说多个权限中，只要有个权限通过则通过。 我们也可以定义为 “and” 关系
```
$auth->check('rule1,rule2',uid,'and');
```
第三个参数指定为"and" 表示多个规则以and关系进行认证， 这时候多个规则同时通过认证才有权限。只要一个规则没有权限则就会返回false。

Auth认证，一个用户可以属于多个用户组。 比如我们对 show_button这个规则进行认证， 用户A 同时属于 用户组1 和用户组2 两个用户组 ， 用户组1 没有show_button 规则权限， 但如果用户组2 有show_button 规则权限，则一样会权限认证通过。
```
$auth->getRoleUser($uid)
```
通过上面代码，可以获得用户所属的所有用户组，方便我们在网站上面显示。

Auth类还可以按用户属性进行判断权限， 比如
按照用户积分进行判断， 假设我们的用户表 (gold_users) 有字段 score 记录了用户积分。
我在规则表添加规则时，定义规则表的condition 字段，condition字段是规则条件，默认为空 表示没有附加条件，用户组中只有规则 就通过认证。
如果定义了 condition字段，用户组中有规则不一定能通过认证，程序还会判断是否满足附加条件。
比如我们添加几条规则：

> `name`字段：grade1 `condition`字段：{score}<100 <br/>
> `name`字段：grade2 `condition`字段：{score}>100 and {score}<200<br/>
> `name`字段：grade3 `condition`字段：{score}>200 and {score}<300

这里 `{score}` 表示 `gold_users` 表 中字段 `score` 的值。

那么这时候

> $auth->check('grade1', uid) 是判断用户积分是不是0-100<br/>
> $auth->check('grade2', uid) 判断用户积分是不是在100-200<br/>
> $auth->check('grade3', uid) 判断用户积分是不是在200-300
