<?php
/**
 * @desc post
 * @param $name
 * @param $default
 * @return string
 */
function post($name = '', $default = null)
{
    if (is_array($name)) {
        $p = [];$rules = [];
        $value = request()->post();
        foreach ($name as $v) {
            if (is_array($v)) {
                if (isset($v[1])) {
                    $default = $v[1];
                }
                $p[$v[0]] = isset($value[$v[0]]) ? $value[$v[0]] : $default;
                if (!empty($v[2])) {
                    $title = empty($v[3]) ? $v[0] : $v[0] . '|' . $v[3];
                    $rules[$title] = $v[2];
                }
            } else {
                $p[$v] = isset($value[$v]) ? $value[$v] : null;
            }
        }

        if ($rules) {
            $validate = app('validate');
            $validate->rule($rules);
            if (!$validate->check($p)) {
                error($validate->getError())->send();
                exit;
            }
        }
        return $p;
    }
    return request()->post($name, $default);
}

/**
 * @desc get
 * @param $name
 * @param $default
 * @return string
 */
function get($name = '', $default = null)
{
    return request()->get($name, $default);
}

/**
 * @desc 返回成功json
 * @param $msg
 * @param array $data
 * @return string
 */
function success ($msg = '操作成功', array $data = [])
{
    return json([
        'code' => 0,
        'msg'  => $msg,
        'data' => $data
    ]);
}

/**
 * @desc 返回错误json
 * @param string $msg
 * @param array $data
 * @return string
 */
function error ($msg = '操作失败', array $data = [])
{
    return json([
        'code' => 1,
        'msg'  => $msg,
        'data' => $data
    ]);
}

/**
 * @desc 返回错误json
 * @param string $count
 * @param array $data
 * @return string
 */
function table_json ($count, array $data = [])
{
    return json([
        'code'  => 0,
        'msg'   => '',
        'count' => $count,
        'data'  => $data
    ]);
}

/**
 * @desc 树形结构
 * @author ww
 * @date 2018/12/29 21:41
 * @param $arr
 * @param int $pid
 * @return array
 */
function get_tree($arr, $pid = 0)
{
    $treeArray = [];
    foreach ($arr as $v) {
        if ($v['pid'] == $pid) {
            $v['children'] = get_tree($arr, $v['id']);
            $treeArray[] = $v;
        }
    }
    return $treeArray;
}

/**
 * 查找家谱树
 * @param $arr array  要分类的数组
 * @param $pid string  父id
 * @return array
 */
function get_family($arr, $pid)
{
    static $list = array();
    foreach ($arr as $v) {
        if ($v['id'] == $pid) {
            $list[] = $v;
            getFamily($arr, $v['parent_id']);
        }
    }
    return $list;
}

/**
 * @desc 得到带level的数组
 * @param $arr
 * @param int $pid
 * @param int $level
 * @return mixed
 */
function get_level_array ($arr, $pid = 0, $level = 0)
{
    static $treeArr = [];
    foreach ($arr as $v) {
        if ($v['pid'] == $pid ) {
            $v['level'] = $level;
            array_push($treeArr, $v);
            get_level_array($arr, $v['id'], $level+1);
        }
    }
    return $treeArr;
}

/**
 * 密码加密方式
 * @param   string   密码
 * @return    string   加密完成的密码
 */
function admin_md5($pwd)
{
    return base64_encode(md5($pwd, true));
}

/**
 * 加密
 * @param string $token 需要被加密的数据
 * @param string $private_key 密钥
 * @return string
 */
function encrypt($token = '', $private_key = '')
{
    return base64_encode(openssl_encrypt($token, 'BF-CBC', md5($private_key), null, substr(md5($private_key), 0, 8)));
}

/**
 * 解密
 * @param string $en_token 加密数据
 * @param string $private_key 密钥
 * @return string
 */
function decrypt($en_token = '', $private_key = '')
{
    return rtrim(openssl_decrypt(base64_decode($en_token), 'BF-CBC', md5($private_key), 0, substr(md5($private_key), 0, 8)));
}


/**
 * @desc 构建参数
 */
function build_params($params)
{
    $request = request();
    $where = [];
    foreach ($params as $v) {
        list ($name, $field, $rule) = $v;
        if ($rule == '=') {
            $value = $request->get($name);
            if (isset($value) && $value != '') {
                $where[] = [$field, '=', $value];
            }
        } else if ($rule == 'like') {
            $value = $request->get($name);
            if ($value) {
                $where[] = [$field, 'like', $value . '%'];
            }
        } else if ($rule == 'between') {
            $value = $request->get($name . '/a');
            if (!empty($value[0])) {
                $where[] = [$field, 'EGT', $value[0]];
            }

            if (!empty($value[1])) {
                $where[] = [$field, 'ELT', $value[1]];
            }
        }
    }

    $page  = $request->get('page', 1);
    $limit = $request->get('limit', 25);

    $limit  = ($page-1)*$limit . ',' . $limit;

    return [$where, $limit];
}

/**
 * TODO 修改 https 和 http
 * @param $url $url 域名
 * @param int $type  0 返回https 1 返回 http
 * @return string
 */
function setHttpType($url, $type = 0)
{
    $domainTop = substr($url,0,5);
    if($type){ if($domainTop == 'https') $url = 'http'.substr($url,5,strlen($url)); }
    else{ if($domainTop != 'https') $url = 'https:'.substr($url,5,strlen($url)); }
    return $url;
}





