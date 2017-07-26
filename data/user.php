<?php
class User{
    const TABLE_NAME = 'zhihu';


    public static function addOneUser($data){
        $ret = mysql::getInstance()->insertOne(self::TABLE_NAME, $data);
        return $ret;
    }

    public static function addMultiUser($data){
        $ret = mysql::getInstance()->insertMulti(self::TABLE_NAME, $data);
        return  $ret;
    }

    public static function update($data){
        $ret = mysql::getInstance()->update(self::TABLE_NAME, $data);
        return $ret;
    }

    public static function delete($data){
        $ret = mysql::getInstance()->delete(self::TABLE_NAME, $data);
        return $ret;
    }

    public static function getOne($fields, $data){
        $ret = mysql::getInstance()->getOne($fields, self::TABLE_NAME, $data);
        return $ret;
    }

    public static function getAll($fields, $data){
        $ret = mysql::getInstance()->getAll($fields, self::TABLE_NAME, $data);
        return $ret;
    }

    public static function getCount($count_field, $data){
        $ret = mysql::getInstance()->getCount($count_field, self::TABLE_NAME, $data);
        return $ret;
    }
}