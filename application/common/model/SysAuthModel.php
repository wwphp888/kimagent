<?php
namespace app\common\model;

class SysAuthModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'fauth';

    /**
     * @desc 菜单权限初始化
     */
    public static function AuthInit()
    {
        if (!session('accessUrl') || !session('menus')) {
            $result = self::where('status', 1)->order('sort')->select();

            $menus = $accessUrl = [];
            foreach ($result as $v) {
                if ($v['type'] == 1) {
                    array_push($menus, $v);
                }
                if ($v['url']) {
                    array_push($accessUrl, $v['url']);
                }
            }

            session('accessUrl', $accessUrl);
            session('menus', get_tree($menus));
        }
    }

    /**
     * @desc 得到权限菜单
     * @param bool $isMenu
     * @param bool $isTree
     * @return array
     */
    public static function getAuth($isMenu = false, $isTree = false)
    {
        $where = [];
        if ($isMenu) {
            $where['type'] = 1;
        }
        $list = self::where($where)->order('sort')->select();
        return true === $isTree ? get_tree($list) : $list;
    }


    /**
     * @desc 得到带level权限
     * @param bool $isMenu
     * @return array
     */
    public static function getAuthLevel($isMenu = false)
    {
        $auth = get_level_array(self::getAuth($isMenu));
        return array_map(function ($v) {
            $str = '';
            for ($i = 0; $i < $v['level']; $i++) {
                $str .= '----';
            }
            $v['title'] = $str . $v['title'];
            return $v;
        }, $auth);
    }

    /**
     * @desc 清除权限缓存
     */
    public static function clearAuthCache()
    {
        session('menus', null);
        session('accessUrl', null);
    }
}