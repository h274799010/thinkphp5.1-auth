/*
 Navicat Premium Data Transfer

 Source Server         : 本地
 Source Server Type    : MySQL
 Source Server Version : 50717
 Source Host           : localhost:3306
 Source Schema         : gold

 Target Server Type    : MySQL
 Target Server Version : 50717
 File Encoding         : 65001

 Date: 05/07/2018 12:20:28
*/

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
  `path` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '角色路径',
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
  `path` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '父级路径',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '规则表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
