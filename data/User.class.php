<?php
namespace data;
use data\mysql\driver\Model;
class User{
    const TABLE_NAME = 'zhihu';


    public static function addOneUser($data){
        $ret = Model::getInstance(self::TABLE_NAME)->add($data);
        return $ret;
    }

    public static function update($where, $data){
        $ret = Model::getInstance(self::TABLE_NAME)->where($where)->save($data);
        return $ret;
    }

    public static function delete($data){
        $ret = Model::getInstance(self::TABLE_NAME)->where($data)->delete();
        return $ret;
    }

    public static function getOne($fields, $where){
        $ret = Model::getInstance(self::TABLE_NAME)->field($fields)->where($where)->limit('1')->select();
        return $ret;
    }

    public static function getAll($fields, $where){
        $ret = Model::getInstance(self::TABLE_NAME)->field($fields)->where($where)->select();
        return $ret;
    }

    public static function getCount($count_field){
        $ret = Model::getInstance(self::TABLE_NAME)->count($count_field);
        return $ret;
    }

    public static function fetchSql(){
        return Model::getInstance(self::TABLE_NAME)->fetchSql();
    }

    public static function close(){
        Model::getInstance(self::TABLE_NAME)->close();
    }
}