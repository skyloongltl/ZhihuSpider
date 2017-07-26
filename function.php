<?php

/**
 * 将unicode码转化为中文，再从json转化为数组,并返回需要字段
 * @param $followingData
 * @return mixed
 */
function getFollowingInfo($followingData)
{
    $json = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $followingData);
    $data = json_decode(strip_tags($json), true);
    $followingArray = array();
    $followingArray['paging'] = $data['paging'];
    $followingArray['error'] = $data['error'];
    $size = count($data['data']);
    for ($i = 0; $i < $size; $i++) {
        $followingArray['data'][$i] = array_filter($data['data'][$i], 'filter_key', ARRAY_FILTER_USE_KEY);
    }
    unset($data);
    return $followingArray;
}

/**
 * 筛选字段
 * @param $key
 * @return bool
 */
function filter_key($key)
{
    switch ($key) {
        case 'url_token':
            return true;
        case 'gender':
            return true;
        default:
            return false;
    }
}

/**
 * @param $match
 * @return string
 */
function replace_unicode_escape_sequence($match)
{
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}

function getUserInfo($result){
    $user_info = array();
    /*preg_match('%(https://www.zhihu.com/people/)([^">]*)%i', $result, $out);
    $user_info['user_id'] = empty($out[2]) ? '' : strip_tags($out[2][0]);*/

    preg_match_all('%(<\/svg>\s*<\/div>\s*\S*\s*)(<div\s*class="ProfileHeader-divider">|<\/div>\s*<div\s*class="ProfileHeader-infoItem">)%i', $result, $out);
    $user_info['job'] = empty($out[1]) ? '' : strip_tags($out[1][0]);
    $user_info['university'] = empty($out[1][1]) ? '' : strip_tags($out[1][1]);

    preg_match_all('%(<div\s*class="ProfileHeader-divider"><\/div>)(\s*\S*\s*)%i', $result, $out);
    $user_info['jobex'] = empty($out[2][1]) ? '' : strip_tags($out[2][1]);
    $user_info['profession'] = empty($out[2][0]) ? '' : strip_tags($out[2][0]);

    return $user_info;
}

/**
 * @param string $logic
 * @param $data
 * @return array
 */
/*
*$where = array(
*    'or' => array('name= '=>'',)//如果没有or默认and
*                  'id<' => '',
*)
*/
function getSql($logic = 'and', $data){
    if($logic == 'and'){
        $keys = array_keys($data);
        $size = count($keys);
        $where = array();
        for ($i = 0; $i < $size; $i++){
            $where[$i] = $keys[$i] . '?';
        }
        $field = implode(' and ', $where);
        $value = array_values($data);
        $ret = array(
            'field' => $field,
            'value' => $value,
        );
        return $ret;
    }else{
        $or = $data['or'];
        unset($data['or']);
        $and_where = getSql('and', $data);
        $or_keys = array_keys($or);
        $size = count($or_keys);
        $or_where = array();
        for ($i = 0; $i < $size; $i++){
            $or_where[$i] = $or_keys[$i] . '=?';
        }
        $or_where = implode(' or ', $or_where);
        $where = $and_where . ' or ' . $or_where;
        $ret = array(
            'field' => $where,
            'value' => array_merge($and_where['value'], array_values($or)),
        );
        return $ret;
    }
}

function mergeArray($array){
    static $arr = array();
    if(is_array($array)) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                mergeArray($v);
            } else {
                $arr[] = $v;
            }
        }
    }
    return $arr;
}


function getUserFollow($type = '',$tmp_u_id, $offset = 0, $url = ''){
    $cookie = CONFIG['userConfig']['cookie'];
    $include = CONFIG['userConfig']['follow_include'];
    $limit = 20;
    $redis = predis::getInstance();
    $header = array(
        'Authorization: Bearer 2|1:0|10:1500900647|4:z_c0|92:Mi4wQUZDQ3ZEckNtQXNBZ01LekJLbVJDeVlBQUFCZ0FsVk5KM3FkV1FCelVYeGdBMDdoMkJsVDFob0puU040Tm16aHlR|fc953414e362fc4dd8efdae84ce99cf1f57654e7df9953ed04fa0db125a93dea',
    );
    $url = "https://www.zhihu.com/api/v4/members/{$tmp_u_id}/{$type}?include={$include}&offset={$offset}&limit={$limit}";
    echo "-------------start curl " . $tmp_u_id . ' [' .($offset/20+1) . '] ' . " page " . $type . "------------\n";
    ob_flush();
    $result = Request::curl($url, $cookie, $header);
    $user_following = getFollowingInfo($result);
    /*if(isset($user_following['paging']) === false){
        echo '--------------again curl ' . $tmp_u_id . " $type----------";
        getUserFollow($type, $tmp_u_id, $offset, $url);
    }*/
    global $i;
    if(iseet($user_following['error'])){
	exit($i);
    }
	sleep(20);
    if((empty($user_following['data']) || !isset($user_following['data']))){
        echo "------------ " . $tmp_u_id . $type . " is empty------------\n";
        error_log('curl ' . $tmp_u_id . $type . " is empty\n", 3, './error.log');
        return;
    }
    foreach ($user_following['data'] as $v) {
        if (!$redis->sContains('already_request_queue', $v['url_token']) && !$redis->sContains('request_queue', $v['url_token'])){
            $redis->sAdd('request_queue', $v['url_token']);
        }
        $data = array('user_id' =>  $v['url_token'], 'sex'  =>  $v['gender']);
        User::addOneUser($data);
    }
    if($user_following['paging']['is_end'] == false && isset($user_following['paging']['is_end'])){
        $offset += $offset + 20;
        $url = "https://www.zhihu.com/api/v4/members/{$tmp_u_id}/{$type}?include={$include}&offset={$offset}&limit={$limit}";
        getUserFollow($type, $tmp_u_id, $offset, $url);
    }else{
        echo "-------------curl " . $tmp_u_id . $type . " is end-------------\n";
    }
}

function getUser($tmp_u_id){
    $cookie = CONFIG['userConfig']['cookie'];
    $url = "https://www.zhihu.com/people/$tmp_u_id/activities";
    echo "--------------start curl " . $tmp_u_id . " userInfo------------\n";
    $result = Request::curl($url, $cookie);
    if(empty($result) || !isset($result)){
        echo "---------------curl failed------------\n";
        error_log('curl ' . $tmp_u_id . ' userInfo is failed'."\n");
        return false;
    }
    $ret = getUserInfo($result);
    echo "--------------curl " . $tmp_u_id . "userInfo end-------------\n";
    return $ret;
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
