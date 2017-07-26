<?php
class Mysql{
    public static $instances;
    public $pdo;

    public function __construct(){

        $dbname = CONFIG['dbConfig']['dbname'];
        try{
            $this->pdo = new \PDO("mysql:dbname={$dbname};host=127.0.0.1", CONFIG['dbConfig']['user'], CONFIG['dbConfig']['password'], array(PDO::ATTR_PERSISTENT => true));
        }catch (PDOException $e){
            echo 'connect mysql error:'.$e->getMessage();
            return false;
        }
    }

    public static function getInstance()
    {
        $key = getmypid();
        self::$instances = array();
        //初始化连接
        if (empty(self::$instances[$key]))
        {
            self::$instances[$key] = new self();
        }
        return self::$instances[$key];
    }

    /**
     * @param $table
     * @param $data
     * @return bool|string
     */
    /*$where  = array(
       'field' => 'value',
    );*/
    public function insertOne($table, $data = array()){
        $fields = array_keys($data);
        $field  = '(';
        $field .= implode(',', $fields);
        $field .= ')';

        $size = count($data);
        $param = array_fill(0, $size, '?');
        $value  = '(';
        $value .= implode(',', $param);
        $value .= ')';

        $sql = implode(' ', array(
           ' REPLACE INTO ',
            $table,
            $field,
            ' VALUES ',
            $value,
        ));
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(array_values($data));
        if($result === false){
            //TODO
            error_log('error insertOne:'.$stmt->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * @param $table
     * @param $data
     * @return bool|string
     */
    /*$data = array(
        'fields' => array(),
        'values' => array(array(),
            array(),
         ),
      );*/
    public function insertMulti($table, $data = array()){
        $fields = array_values($data['fields']);
        $field  = '(';
        $field .= implode(',', $fields);
        $field .= ')';

        $fields_size = count($data['fields']);
        $values_size = count($data['values']);
        $params = array_fill(0, $fields_size, '?');
        $value = '(';
        $value .= implode(',', $params);
        $value .= ')';

        $sql = implode(' ', array(
           ' REPLACE INTO ',
            $table,
            $field,
            ' VALUES ',
            $value,
        ));

        $stmt = $this->pdo->prepare($sql);
        var_dump($sql);
        foreach ($data['values'] as $v){
            $result = $stmt->execute($v);
            if($result === false){
                //TODO
                error_log('error insertMulti:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            }
        }
        return $this->pdo->lastInsertId();
    }

    /*
     * $data = array(
     *      'data'=>array('field' =>  'value'),
     *      'where' => array(
     *                     'or' => array('name ='=>'',)//如果没有or默认and
     *                  )
     * );
     */
    public function update($table, $data = array()){
        $data_size = count($data['data']);
        $keys = array_keys($data['data']);
        $values = array();
        for ($i = 0; $i < $data_size; $i++){
            $value  = $keys[$i];
            $value .= '=? ';
            $values[] = $value;
        }

        if(array_key_exists('or', $data['where'])){
            $where = getSql('or', $data['where']);
        }else{
            $where = getSql('and', $data['where']);
        }

        $sql = implode(' ', array(
            ' UPDATE ',
            $table,
            ' SET ',
            implode(',', $values),
            ' WHERE ',
            $where['field']
        ));

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(array_merge(array_values($data['data']), $where['value']));
        if($result == false){
            //TODO
            error_log('error update:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $stmt->rowCount();
    }

    public function delete($table, $data = array()){
        if(array_key_exists('or', $data)){
            $where = getSql('or', $data);
        }else{
            $where = getSql('and', $data);
        }

        $sql = implode(' ', array(
            ' DELETE FROM ',
            $table,
            ' WHERE ',
            $where['field']
        ));
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($where['value']);
        if($result == false){
            //TODO
            error_log('error delete:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $stmt->rowCount();
    }

    /*$data1 = array(
        'where' =>  array('university like '  => '%清华%', 'user_id =' => 'ltl-29'),
        );*/
    public function getOne($field = array(), $table = '', $data = array()){

        if(array_key_exists('or', $data)){
            $where = getSql('or', $data);
        }else{
            $where = getSql('and', $data);
        }

        $sql = implode(' ', array(
           ' SELECT ',
            implode(',', $field),
            ' FROM ',
            $table,
            ' WHERE ',
            $where['field'],
            ' LIMIT 1',
        ));
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($where['value']);
        if($result === false){
            //TODO
            error_log('error getOne:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($field, $table, $data = array()){
        if(array_key_exists('or', $data)){
            $where = getSql('or', $data);
        }else{
            $where = getSql('and', $data);
        }

        $sql = implode(' ', array(
           ' SELECT ',
            implode(',', $field),
            ' FROM ',
            $table,
            ' WHERE ',
            $where['field'],
        ));
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($where['value']);
        if($result == false){
            //TODO
            error_log('error getAll:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getCount($count_field,$table, $data){
        if(array_key_exists('or', $data)){
            $where = getSql('or', $data);
        }else{
            $where = getSql('and', $data);
        }

        $sql = 'SELECT count(' . $count_field . ') FROM ' . $table . ' WHERE' .  $where["field"];
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(array_values($where['value']));
        if($result === false){
            error_log('error getCount:'.$this->pdo->errorInfo() . "\n", 3 , '../error.log');
            return false;
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function close(){
        $this->pdo = null;
    }
}