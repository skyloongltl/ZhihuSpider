<?php
namespace data;
class Request{

    public static function curl($url = '', $cookie = '', $header = array()){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIE,$cookie);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0');
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.zhihu.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(curl_errno($ch)){
            var_dump($httpCode);
            return curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param $user_ids
     * @param $type
     * @param $follow_include
     * @param $offset
     * @param $limit
     * @param $cookie
     * @return array
     */
    public static function curlMultiFollow($user_ids = array(), $type = '', $cookie = '', $follow_include = '', $offset = 0, $limit = 20){
        $count = count($user_ids);
        $max_size = ($count > 5) ? 5 : $count;
        $requestMap = array();

        $mh = curl_multi_init();
        for($i = 0; $i < $max_size; $i++){
            switch ($type) {
                case 'follow':
                    $header = array(
                        'Authorization: Bearer Mi4wQUJDS1JlUkNSQWtBZ01LekJLbVJDeGNBQUFCaEFsVk5xODZYV1FCbUotVGVCQURJUUpLZThIdm5lTUFzTkZUcjF3|1500529067|354cc5c9a56b01d5b4ba9a402c2c88e38180b663'
                    );
                    $url = "https://www.zhihu.com/api/v4/members/{$user_ids[$i]}/{$type}?include={$follow_include}&offset={$offset}&limit={$limit}";
                    break;
                case 'user':
                    $url = "https://www.zhihu.com/people/{$user_ids[$i]}/activities";
                    break;
                default:
                    //TODO
                    continue;
            }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            curl_setopt($ch, CURLOPT_REFERER, 'https://www.zhihu.com');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $requestMap[$i] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        $data = array(
            'user_followings_info' => array(),
            'users_info'           => array(),
        );

        $active = null;
        do{
            $mrc = curl_multi_exec($mh, $active);
            if ($mrc != CURLM_OK) {break;}
            while($done = curl_multi_info_read($mh)){
                $info = curl_getinfo($done['handle']);
                $user_following = curl_multi_getcontent($done['handle']);
                $error = curl_error($done['handle']);

                if($type == 'follow') {
                    $data['user_followings_info'][] = getFollowingInfo($user_following);
                }else{
                    $users_info['users_info'][] = getUserInfo($user_following);
                }

                if($i < count($user_ids) && isset($user_ids[$i])){
                    switch ($type) {
                        case 'follow':
                            $header = array(
                                'Authorization: Bearer Mi4wQUJDS1JlUkNSQWtBZ01LekJLbVJDeGNBQUFCaEFsVk5xODZYV1FCbUotVGVCQURJUUpLZThIdm5lTUFzTkZUcjF3|1500529067|354cc5c9a56b01d5b4ba9a402c2c88e38180b663'
                            );
                            $url = "https://www.zhihu.com/api/v4/members/{$user_ids[$i]}/{$type}?include={$follow_include}&offset={$offset}&limit={$limit}";
                            break;
                        case 'user':
                            $url = "https://www.zhihu.com/people/{$user_ids[$i]}/activities";
                            break;
                        default:
                            //TODO
                            continue;
                    }
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_REFERER, 'https://www.zhihu.com');
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $requestMap[$i] = $ch;
                    curl_multi_add_handle($mh, $ch);
                    $i++;
                }
                curl_multi_remove_handle($mh, $done['handle']);
            }

            if ($active)
                curl_multi_select($mh, 10);

        }while($active || $mrc === CURLM_CALL_MULTI_PERFORM);

        curl_multi_close($mh);
        return $data;
    }
}