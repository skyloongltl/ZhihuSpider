<?php
namespace data\mysql\driver;
class Field {
    private $name = null;
    private $comp = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function addOperator($operator, $value, $logic){
        $this->comp[] = $logic;
        $this->comp[] = array('name'    =>  $this->name, 'operator' =>  $operator, 'value'  =>  $value);
    }

    public function getComps(){
        return $this->comp;
    }

    public function isIncomplete(){
        return empty($this->comp);
    }
}