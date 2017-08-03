<?php
namespace data\mysql\domain;
class zhihuDomainObject extends DomainObject {
   private $attributes = array();

    public function __set($name, $value){
        $this->attributes[$name] = $value;
    }
    
    public function __get($name)
    {
        return empty($this->attributes[$name]) ? false : $this->attributes[$name];
    }
    
    public function getAll(){
        return $this->attributes;
    }
    
    public function getAllKeys(){
        return array_keys($this->attributes);
    }
    
    public function getAllValues(){
        return array_values($this->attributes);
    }
}