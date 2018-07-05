<?php
/**
 * Created by PhpStorm.
 * User: HuangSen
 * Date: 2018/7/5
 * Time: 12:24
 * @author: Huang
 */

namespace huangsen\auth\model;

use think\Model;

class AuthRole extends Model
{
    /**
     * * 检查角色是否全部存在
     * @param array|string $gid 角色id列表
     * @return bool
     */
    public function checkGroupId($gid)
    {
        return $this->checkId('AuthRole', $gid, '以下角色id不存在:');
    }

    /**
     * 检查id是否全部存在
     * @param $modelname
     * @param $mid
     * @param string $msg
     * @return bool
     * @author: Huang
     */
    public function checkId($modelname, $mid, $msg = '以下id不存在:')
    {
        if (is_array($mid)) {
            $count = count($mid);
            $ids = implode(',', $mid);
        } else {
            $mid = explode(',', $mid);
            $count = count($mid);
            $ids = $mid;
        }
        $s = model($modelname)->where('id', $ids)->column('id');
        if (count($s) === $count) {
            return true;
        } else {
            $diff = implode(',', array_diff($mid, $s));
            $this->error = $msg . $diff;
            return false;
        }
    }

    /**
     * 把用户添加到角色,支持批量添加用户到角色
     * 示例: 把uid=1的用户添加到group_id为1,2的组 `AuthGroupModel->addToGroup(1,'1,2');`
     * @param $uid
     * @param $gid
     * @return bool
     * @author: Huang
     */
    public function addToRole($uid, $gid)
    {
        $uid = is_array($uid) ? implode(',', $uid) : trim($uid, ',');
        $gid = is_array($gid) ? $gid : explode(',', trim($gid, ','));
        $Access = model('AuthRoleUser');
        if (isset($_REQUEST['batch'])) {
            //为单个用户批量添加角色时,先删除旧数据
            $del = $Access->where('user_id', $uid)->delete();
        }
        $uid_arr = explode(',', $uid);
        $uid_arr = array_diff($uid_arr, array(1));
        $add = array();
        if ($del !== false) {
            foreach ($uid_arr as $u) {
//                //判断用户id是否合法
//                if (model('users')->getFieldByid($u, 'id') == false) {
//                    $this->error = "id为{$u}的用户不存在！";
//                    return false;
//                }
                foreach ($gid as $g) {
                    if (is_numeric($u) && is_numeric($g)) {
                        $add[] = array('group_id' => $g, 'uid' => $u);
                    }
                }
            }
            $Access->saveAll($add);
        }
        if ($Access->getError()) {
            if (count($uid_arr) == 1 && count($gid) == 1) {
                //单个添加时定制错误提示
                $this->error = "不能重复添加";
            }
            return false;
        } else {
            return true;
        }
    }
}