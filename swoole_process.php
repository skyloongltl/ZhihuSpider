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
$workers = array();

while(true){
    echo "--------begin get user info--------\n";
    $total = $redis->sCard('request_queue');
    $current_count = ($total <= $max_connect) ? $total : $max_connect;
    if ($total == 0) {
        echo "--------done--------\n";
        break;
    }

    if ($redis->get('error') == "true") {
        echo "----------error-------\n";
        error_log('here have been some errors', 3, DIR . '/error.log');
        break;
    }

    for ($i = 0; $i < $max_connect; $i++){
        $process = new swoole_process('getData', false, false);
        $pid = $process->start();
        $workers[$pid] = $process;
        usleep("1");
    }

    for ($i = 0; $i < $max_connect; $i++){
        $ret = swoole_process::wait();
        $pid = $ret['pid'];
        unset($workers[$pid]);
        echo "worker $pid exit".PHP_EOL;
    }
}

function getData(swoole_process $process){
    global $redis;
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
        $process->exit(0);
    }
    $where = array(
        'user_id' => $tmp_u_id
    );
    \data\User::update($where, $userInfo);
    $endTime = microtime_float();
    $totalTime = $endTime - $startTime;
    echo "------------total " . $totalTime . "seconds on " . $tmp_u_id . "-----------\n";
    $process->exit(0);
}