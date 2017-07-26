<?php
set_time_limit(0);
require_once './data/config.php';
require_once './data/Request.php';
require_once './data/mysql.php';
require_once './data/predis.php';
require_once './function.php';
require_once './data/user.php';

$redis = predis::getInstance();
//already_request_queue;request_queue
$redis->sAdd('request_queue', 'gao-tai-ye');
$max_connect = 1;
while(1){
    echo "--------begin get user info--------\n";
    $total = $redis->sCard('request_queue');
    if ($total == 0)
    {
        echo "--------done--------\n";
        break;
    }

    $current_count = ($total <= $max_connect) ? $total : $max_connect;

    for ($i = 1; $i <= $current_count; ++$i){
        $pid = pcntl_fork();
        if ($pid == -1)
        {
            echo "--------fork child process failed--------\n";
            exit(0);
        }
        if(!$pid){
            $startTime = microtime_float();
            $tmp_redis = predis::getInstance();
            $tmp_u_id = $tmp_redis->sPop('request_queue');
            getUserFollow('followers', $tmp_u_id);
            getUserFollow('followees', $tmp_u_id);
            $userInfo = getUser($tmp_u_id);
            $redis->sAdd('already_request_queue', $tmp_u_id);
            $redis->close();
            if($userInfo === false){
                echo "--------------unknow error-----------";
                exit($i);
            }
            $data = array(
                'data'  =>  $userInfo,
                'where' =>  array('user_id ='   =>  $tmp_u_id),
            );
            User::update($data);
            $endTime = microtime_float();
            $totalTime = $endTime - $startTime;
            echo "------------total " . $totalTime . "seconds on " . $tmp_u_id ."-----------\n";
            exit($i);
        }
        usleep("1");
    }
    while (pcntl_waitpid(0, $status) != -1)
    {
        $status = pcntl_wexitstatus($status);
        if (pcntl_wifexited($status))
        {
            echo "yes";
        }
        echo "--------$status finished--------\n";
    }
}
