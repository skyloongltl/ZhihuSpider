<?php
namespace data\mysql\domain;
abstract class DomainObject{
    protected  $id;

    public function __construct($id = null) {
        if (is_null($id)) {
            $this->id = -1;
        } else {
            $this->id = $id;
        }
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }
}