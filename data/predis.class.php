<?php
namespace data;
class predis{

    public static function getInstance()
    {
        static $instances = array();
        $key = getmypid();
        if (empty($instances[$key])) {
            $instances[$key] = new \Redis();

            $instances[$key]->connect('127.0.0.1', '6379');
        }
        return $instances[$key];
    }
}