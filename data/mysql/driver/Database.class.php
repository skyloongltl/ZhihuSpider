<?php
namespace data\mysql\driver;
class Database{
    public static $instance;
    public $pdo;

    public function __construct()
    {
        $config = require DIR . "/data/mysql/db.config.php";
        $dsn = "{$config['DB_TYPE']}:dbname={$config['DB_NAME']};host={$config['DB_HOST']};charset=utf8mb4";
        try{
            $this->pdo = new \PDO($dsn, $config['DB_USER'], $config['DB_PWD'], array(\PDO::ATTR_PERSISTENT => true));
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }catch (\PDOException $e){
            echo $e->getMessage();
        }
    }

    public static function getInstance(){
        $key = getmypid();
        self::$instance = array();
        if(!isset(self::$instance[$key])){
            self::$instance[$key] = new self();
        }
        return self::$instance[$key];
    }
}