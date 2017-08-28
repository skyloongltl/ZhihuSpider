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
    empty($followingArray['error']) ?  : $followingArray['error'] = $data['error'];
    $size = count($data['data']);
    for ($i = 0; $i < $size; $i++) {
        $followingArray['data'][$i] = array_filter($data['data'][$i], 'filter_key', ARRAY_FILTER_USE_KEY);
    }
    unset($data);
    return $followingArray;
}

function getUserInfo($user_data){
    $json = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $user_data);
    $data = json_decode(strip_tags($json), true);
    $user_info = array();
    empty($data['error']) ?  : $user_info['error'] = $data['error'];
    empty($data['business']) ? $user_info['business'] = 'bare' : $user_info['business'] = $data['business']['name'];
    empty($data['follower_count']) ? $user_info['follower_count'] = 0 : $user_info['follower_count'] = $data['follower_count'];
    empty($data['following_count']) ? $user_info['following_count'] = 0 : $user_info['following_count'] = $data['following_count'];
    if(!empty($data['educations'])){
        foreach ($data['educations'] as $key => $val){
            $user_info['major']  .= (empty($val['major'])  ? 'bare' : ",{$val['major']['name']}");
            $user_info['school'] .= (empty($val['school']) ? 'bare' : ",{$val['school']['name']}");
        }
    }else{
        $user_info['major']  = 'bare';
        $user_info['school'] = 'bare';
    }
    if(!empty($data['employments'])){
        foreach ($data['employments'] as $key => $val){
            $user_info['company']    .= (empty($val['company']) ? 'bare' : ",{$val['company']['name']}");
            $user_info['job']        .= (empty($val['job'])     ? 'bare' : ",{$val['job']['name']}");
        }
    }else{
        $user_info['company'] = 'bare';
        $user_info['job']     = 'bare';
    }

    if(!empty($data['locations'])){
        foreach ($data['locations'] as $key => $val){
            $user_info['locations'] = (empty($val['name']) ? 'bare' : ",{$val['name']}");
        }
    }else{
        $user_info['locations'] = 'bare';
    }
    return $user_info;
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
    if(empty($data) || !isset($data)){
        return $ret = array();
    }
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
    $redis = \data\predis::getInstance();
    $header = array(
        'Authorization: Bearer Mi4xUVhXdkJBQUFBQUFBZ01LekJLbVJDeGNBQUFCaEFsVk5WcEhMV1FBN1Q2bVBZN0dSdVBUMXRITDZWUGpja2VucTN3|1503921238|fff9dc0d762f64a63b2da2b627c48f7868479732',
    );
    $url = "https://www.zhihu.com/api/v4/members/{$tmp_u_id}/{$type}?include={$include}&offset={$offset}&limit={$limit}";
    echo "-------------start curl $tmp_u_id  [ " . ($offset/20+1) . " ] page $type------------\n";
    echo "url: $url\n";
    $result = \data\Request::curl($url, $cookie, $header);
    $user_following = getFollowingInfo($result);
    if((empty($user_following['data']) || !isset($user_following['data']))){
        echo "------------{$tmp_u_id} {$type} is empty------------\n";
        //error_log('curl ' . $tmp_u_id . $type . " is empty\n", 3, './error.log');
        return;
    }
    if(isset($user_following['error'])){
        //TODO
        $redis->set('error','true');
        $redis->close();
        error_log("There have been some errors", 3, DIR.'/error.log');
        echo "There have been some errors!!!!!";
        return;
    }
    foreach ($user_following['data'] as $v) {
        if (!$redis->sContains('already_request_queue', $v['url_token']) && !$redis->sContains('request_queue', $v['url_token'])){
            $redis->sAdd('request_queue', $v['url_token']);
        }
        $data = array('user_id' => $v['url_token'], 'sex' => $v['gender']);
        \data\User::addOneUser($data);
    }
    sleep(1);
    if($user_following['paging']['is_end'] == false){
        $offset = $offset + 20;
        $url = "https://www.zhihu.com/api/v4/members/{$tmp_u_id}/{$type}?include={$include}&offset={$offset}&limit={$limit}";
        getUserFollow($type, $tmp_u_id, $offset, $url);
    }else{
        echo "-------------curl {$tmp_u_id} {$type} is end-------------\n";
    }
}

function getUser($tmp_u_id){
    $cookie = CONFIG['userConfig']['cookie'];
    $include = "locations%2Cemployments%2Cgender%2Ceducations%2Cbusiness%2Cvoteup_count%2Cthanked_Count%2Cfollower_count%2Cfollowing_count%2Ccover_url%2Cfollowing_topic_count%2Cfollowing_question_count%2Cfollowing_favlists_count%2Cfollowing_columns_count%2Cavatar_hue%2Canswer_count%2Carticles_count%2Cpins_count%2Cquestion_count%2Ccolumns_count%2Ccommercial_question_count%2Cfavorite_count%2Cfavorited_count%2Clogs_count%2Cmarked_answers_count%2Cmarked_answers_text%2Cmessage_thread_token%2Caccount_status%2Cis_active%2Cis_bind_phone%2Cis_force_renamed%2Cis_bind_sina%2Cis_privacy_protected%2Csina_weibo_url%2Csina_weibo_name%2Cshow_sina_weibo%2Cis_blocking%2Cis_blocked%2Cis_following%2Cis_followed%2Cmutual_followees_count%2Cvote_to_count%2Cvote_from_count%2Cthank_to_count%2Cthank_from_count%2Cthanked_count%2Cdescription%2Chosted_live_count%2Cparticipated_live_count%2Callow_message%2Cindustry_category%2Corg_name%2Corg_homepage%2Cbadge%5B%3F(type%3Dbest_answerer)%5D.topics";
    $url = "https://www.zhihu.com/api/v4/members/{$tmp_u_id}?include={$include}";
    $header = array(
        'Authorization: Bearer Mi4xUVhXdkJBQUFBQUFBZ01LekJLbVJDeGNBQUFCaEFsVk5WcEhMV1FBN1Q2bVBZN0dSdVBUMXRITDZWUGpja2VucTN3|1503921238|fff9dc0d762f64a63b2da2b627c48f7868479732',
    );
    echo "----------------start curl {$tmp_u_id} userInfo----------------\n";
    $result = \data\Request::curl($url, $cookie, $header);

    if(empty($result) || !isset($result)){
        echo "----------------curl failed-------------\n";
        return false;
    }
    $ret = getUserInfo($result);
    $redis = \data\predis::getInstance();
    if(isset($ret['error'])){
        //TODO
        $redis->set('error', "true");
        $redis->close();
        error_log("There have been some errors", 3, DIR.'/error.log');
        echo "There have been some errors!!!!!";
        return false;
    }
    echo "--------------curl $tmp_u_id userInfo end------------------\n";
    return $ret;
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
