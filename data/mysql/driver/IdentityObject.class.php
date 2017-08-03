<?php
namespace data\mysql\driver;
class IdentityObject{
    protected $endSentence = array();
    protected $logic = 'AND';
    protected $fields = array();
    protected $currentField = null;
    protected $enforce = array();

    public function __construct($field = null, $enforce = null) {
        if (!is_null($enforce)) {
            $this->enforce = $enforce;
        }

        if (!is_null($field)) {
            $this->field($field);
        }
    }

    public function field($fieldName) {
        if (!$this->isVoid() && $this->currentField->isIncomplete()) {
            throw new \Exception('Incomplete field');
        }


        $this->enforceField($fieldName);

        if (isset($this->fields[$fieldName])) {
            $this->currentField = $this->fields[$fieldName];
        } else {
            $this->currentField = new Field($fieldName);
            $this->fields[$fieldName] = $this->currentField;
        }

        return $this;
    }

    /*public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        switch ($name){
            case 'eq':
                break;
            case 'neq':
                break;
        }
    }*/

    public function eq($value) {
        return $this->operator('=', $value);
    }

    public function neq($value){
        return $this->operator('<>', $value);
    }

    public function egt($value){
        return $this->operator('>=', $value);
    }

    public function elt($value){
        return $this->operator('<=', $value);
    }

    public function gt($value) {
        return $this->operator('>', $value);
    }

    public function lt($value) {
        return $this->operator('<', $value);
    }

    public function like($value){
        return $this->operator('LIKE', $value);
    }

    public function in($value){
        return $this->operator('IN', $value);
    }

    public function notIn($value){
        return $this->operator('NOT IN', $value);
    }

    public function between($value){
        return $this->operator('BETWEEN', $value);
    }
/******************************************************************************/
    public function group($value){
        //return $this->opeartor('GROUP BY', $value);
        return $this->setEndSentence('GROUP BY', $value);
    }

    public function order($value){
        //return $this->opeartor('ORDER BY', $value);
        return $this->setEndSentence('ORDER BY', $value);
    }

    public function limit($value){
        //return $this->opeartor('LIMIT', $value);
        return $this->setEndSentence('LIMIT', $value);
    }

    public function logic($value){
        $this->logic = $value;
        return $this;
    }

/******************************************************************************/

    /*public function alias($value){

    }

    public function distinct($value){

    }*/

/*******************************************************************************/

    private function setEndSentence($operator, $value){
        $this->endSentence[] = "$operator $value";
        return $this;
    }

    private function operator($operator, $value){
        if ($this->isVoid()) {
            throw new \Exception('no object fileds defined!');
        }

        $this->currentField->addOperator($operator, $value, $this->logic);
        return $this;
    }

    public function isVoid() {
        return empty($this->fields);
    }

    public function enforceField($fileName) {
        if (!in_array($fileName, $this->enforce) && !empty( $this->enforce)) {
            // 非法字段
            $forcelist = implode( ', ', $this->enforce );
            throw new \Exception("{$fileName} not a legal field ($forcelist)");
        }
    }

    public function getObjectFields() {
        return $this->enforce;
    }

    public function getComps() {
        $ret = array();
        foreach ($this->fields as  $field) {
            $ret = array_merge($ret, $field->getComps());
        }

        array_shift($ret);
        $ret = array_merge($ret, $this->endSentence);
        return $ret;
    }

    public function combinationFieldCondition() {
        $ret = array();
        foreach($this->getComps() as $compdata) {
            is_array($compdata)
                ? $ret[] = "{$compdata['name']} {$compdata['operator']} {$compdata['value']}"
                : $ret[] = $compdata;
        }

        return implode( " ", $ret );
    }

    public function reset(){
        $this->logic = 'AND';
        $this->fields = array();
        $this->currentField = null;
        $this->endSentence = array();
    }
}

/*require "Field.class.php";
$idobj = new IdentityObject();
$idobj
    ->field("name")
    ->eq("hello")
    ->logic('AND')
    ->field("start")
    ->gt(time())
    ->logic('OR')
    ->lt(time()+(24*60*60))
    ->group('name')
    ->order('name ASC')
    ->limit('1,20')
    ->getComps();
var_dump($idobj->getComps());
echo $idobj->combinationFieldCondition();*/