<?php
set_time_limit(0);
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
define('DIR', dirname(__FILE__));
include DIR . "/data/config.php";
include DIR . "/data/mysql/db.config.php";
include DIR . "/function.php";
require DIR . '/AutoLoading.php';
spl_autoload_register(array('AutoLoading', 'autoload'));

$redis = \data\predis::getInstance();
//already_request_queue;request_queue
$redis->sAdd('request_queue', 'gao-tai-ye');
$redis->set('error', 'false');
$max_connect = 2;
while (1) {
    echo "--------begin get user info--------\n";
    $total = $redis->sCard('request_queue');
    if ($total == 0) {
        echo "--------done--------\n";
        break;
    }

    if ($redis->get('error') == "true") {
        echo "----------error-------\n";
        error_log('here have been some errors', 3, DIR . '/error.log');
        break;
    }

    $current_count = ($total <= $max_connect) ? $total : $max_connect;

    for ($i = 1; $i <= $current_count; ++$i) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo "--------fork child process failed--------\n";
            exit(0);
        }
        if (!$pid) {
            $startTime = microtime_float();
            $tmp_redis = \data\predis::getInstance();
            $tmp_u_id = $tmp_redis->sPop('request_queue');
            getUserFollow('followers', $tmp_u_id);
            getUserFollow('followees', $tmp_u_id);
            $userInfo = getUser($tmp_u_id);
            $redis->sAdd('already_request_queue', $tmp_u_id);
            $redis->close();
            if ($userInfo === false) {
                echo "--------------unknow error-----------\n";
                exit($i);
            }
            $where = array(
                'user_id' => $tmp_u_id
            );
            \data\User::update($where, $userInfo);
            $endTime = microtime_float();
            $totalTime = $endTime - $startTime;
            echo "------------total " . $totalTime . "seconds on " . $tmp_u_id . "-----------\n";
            exit($i);
        }
        usleep("1");
    }
    while (pcntl_waitpid(0, $status) != -1) {
        $status = pcntl_wexitstatus($status);
        if (pcntl_wifexited($status)) {
            echo "yes";
        }
        echo "--------$status finished--------\n";
    }
}
