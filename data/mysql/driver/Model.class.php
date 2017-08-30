<?php
namespace data\mysql\driver;
class Model{
    public $table_name;
    public static $instances = null;
    private $sql_factory;
    private $identity_object;
    private $pdo;
    private $domain;
    private $fields = array();

    public function __construct($table)
    {
        $this->table_name = $table;
        $this->_init();
        $this->constructDomainObject();
    }

    public static function getInstance($table)
    {
        $key = getmypid();
        self::$instances = array();
        //初始化连接
        if (empty(self::$instances[$key]))
        {
            self::$instances[$key] = new self($table);
        }
        return self::$instances[$key];
    }

    private function _init(){
        $this->sql_factory = new SqlFactory();
        $this->identity_object = new IdentityObject();
        $this->pdo = Database::getInstance()->pdo;
    }

    private function constructDomainObject(){
        $content = <<<EOL
<?php
namespace data\mysql\domain;
class {$this->table_name}DomainObject extends DomainObject {
   private \$attributes = array();

    public function __set(\$name, \$value){
        \$this->attributes[\$name] = \$value;
    }
    
    public function __get(\$name)
    {
        return empty(\$this->attributes[\$name]) ? false : \$this->attributes[\$name];
    }
    
    public function getAll(){
        return \$this->attributes;
    }
    
    public function getAllKeys(){
        return array_keys(\$this->attributes);
    }
    
    public function getAllValues(){
        return array_values(\$this->attributes);
    }
}
EOL;

        if(file_exists(DIR."/data/mysql/domain/{$this->table_name}DomainObject.class.php")){
            $class_name = "\\data\\mysql\\domain\\{$this->table_name}DomainObject";
            $this->domain = new $class_name();
        }else {
            file_put_contents(DIR."/data/mysql/domain/{$this->table_name}DomainObject.class.php", $content);
        }
    }

    /**
     * @param $where
     * @return $this
     */
    /*
     * $where = array(
     *          'name'      => array('gt', 1),
     *          'sex'       => array('like', 3),
     *          'age'       => 3,
     *          '_logic'    => 'OR',
     *          'address'   => 'beijing',
     *          'ip'        => '127.0.0.1'
     * );
     */
    public function where($where){
        foreach ($where as $key => $val){
            if(is_array($val)){
                array_change_key_case($val, CASE_LOWER);
                $function_name = $val[0];
                $this->identity_object
                    ->field($key)
                    ->$function_name($val[1]);
            }elseif ($key === '_logic'){
                $this->identity_object
                    ->logic($val);
            }else{
                $this->identity_object
                    ->field($key)
                    ->eq($val);
            }
        }
        return $this;
    }

    public function order($value){
        $this->identity_object->order($value);
        return $this;
    }

    public function group($value){
        $this->identity_object->group($value);
        return $this;
    }

    //limit只能用字符串
    public function limit($value){
        $this->identity_object->limit($value);
        return $this;
    }

    public function data($data = null){
        if(!isset($data)){
            throw new \Exception('data is null');
        }
        foreach ($data as $key => $val){
            $this->domain->$key = $val;
        }
        return $this;
    }

    public function field($value = null){
        if(!isset($value)){
            //TODO
            throw new \Exception();
        }
        if(is_array($value)){
            foreach ($value as $key => $val){
                is_string($key) ? ($this->fields[] = "{$key} as {$val}") : $this->fields[] = $val;
            }
        }elseif(is_string($value)){
            $this->fields = array_merge($this->fields, explode(',', $value));
        }
        return $this;
    }

    //join要在调用where之前调用
    public function join($value){
        if(!isset($value)){
            //TODO
            throw new \Exception();
        }
        $this->identity_object->join($value);
    }

    public function fetchSql(){
        $sql = $this->sql_factory->fetchSql();
        return implode(";\n", $sql);
    }

    /**
     * data是一个键值对数组
     * @param null $data
     * @return mixed
     * @throws \Exception
     */
    public function add($data = null){
        $fields = array();
        if(is_array($data)){
            list($insert_sql, $value) = $this->sql_factory->buildInsertStatement($this->table_name, $data);
        }else {
            $fields = array_merge($fields, $this->domain->getAll());
            list($insert_sql, $value) = $this->sql_factory->buildInsertStatement($this->table_name, $fields);
        }

        try {
            $stmt = $this->pdo->prepare($insert_sql);
            $result = $stmt->execute($value);
            $this->identity_object->reset();
            if ($result === false) {
                //TODO
                var_dump($stmt->errorInfo());
            }
            return $this->pdo->lastInsertId();
        }catch (\PDOException $e){
            if($e->errorInfo[0] == 2006 || $e->errorInfo[0] == 2013 || $e->errorInfo[0] == 70100){
                Database::close();
                $count = 1;
                while (!Database::getInstance()->is_connect){
                    sleep(1);
                    echo "第{$count}次重新连接数据库失败";
                    $count++;
                }
                $this->pdo = Database::getInstance()->pdo;
                $stmt = $this->pdo->prepare($insert_sql);
                $result = $stmt->execute($value);
                $this->identity_object->reset();
                if ($result === false) {
                    //TODO
                    var_dump($stmt->errorInfo());
                }
                return $this->pdo->lastInsertId();
            }else{
                echo $e->errorInfo[2];
            }
        }
    }

    /**
     * data如果不是键值对数组，就不填，并要先调用data()再调用save()
     * @param null $data
     * @return mixed
     * @throws \Exception
     */
    public function save($data = null){
        $fields = array();
        if(is_array($data)){
            list($update_sql, $value) = $this->sql_factory->buildUpdateStatement($this->table_name, $data, $this->identity_object);
        }else {
            $fields = array_merge($fields, $this->domain->getAll());
            list($update_sql, $value) = $this->sql_factory->buildInsertStatement($this->table_name, $fields, $this->identity_object);
        }

        try {
            $stmt = $this->pdo->prepare($update_sql);
            $result = $stmt->execute($value);
            $this->identity_object->reset();
            if ($result === false) {
                //TODO
                var_dump($update_sql, $this->fetchSql());
                var_dump($stmt->errorInfo());
            }
            return $stmt->rowCount();
        }catch (\PDOException $e){
            if($e->errorInfo[0] ==70100 || $e->errorInfo[0] == 2006 || $e->errorInfo[0] == 2013){
                Database::close();
                $count = 1;
                while (!Database::getInstance()->is_connect){
                    sleep(1);
                    echo "数据库第{$count}次重新连接失败\n";
                    $count++;
                }
                $this->pdo = Database::getInstance()->pdo;
                $stmt = $this->pdo->prepare($update_sql);
                $result = $stmt->execute($value);
                $this->identity_object->reset();
                if ($result === false) {
                    //TODO
                    var_dump($update_sql, $this->fetchSql());
                    var_dump($stmt->errorInfo());
                }
                return $stmt->rowCount();
            }else{
                echo $e->errorInfo[2];
            }
        }
    }

    public function select(){
        list($select_sql, $value) = $this->sql_factory->buildSelectStatement($this->table_name, $this->fields, $this->identity_object);

        $stmt = $this->pdo->prepare($select_sql);
        $result = $stmt->execute($value);
        $this->identity_object->reset();
        if($result === false){
            //TODO
            var_dump($stmt->errorInfo());
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function delete(){
        list($delete_sql, $value) = $this->sql_factory->buildDeleteStatement($this->table_name, $this->identity_object);

        $stmt = $this->pdo->prepare($delete_sql);
        $result = $stmt->execute($value);
        $this->identity_object->reset();
        if($result === false){
            //TODO
            var_dump($stmt->errorInfo());
        }

        return $stmt->rowCount();
    }

    public function count($value){
        $amount = $this->field("count({$value}) as amount")->select();
        return $amount[0]['amount'];
    }

    public function close(){
        Database::close();
    }
}