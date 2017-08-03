<?php
namespace data\mysql\driver;
class SqlFactory
{
    protected $join = null;
    private $sqlStmt = array();

    public function buildUpdateStatement($table, array $fields = null, IdentityObject $idobj = null)
    {
        $query = "UPDATE {$table} SET ";
        $query .= implode(" = ?, ", array_keys($fields)) . ' = ?';
        $terms = array_values($fields);
        $query .= " WHERE ";
        list($que ,$ter) = $this->buildWhere($idobj);
        $query .= $que;
        $this->sqlStmt[] = array($query, array_merge($terms, $ter));
        return array($query, array_merge($terms, $ter));
    }

    public function buildInsertStatement($table, $fields)
    {
        $terms = array();
        $qs = array();
        $query = "REPLACE INTO {$table} (";
        $query .= implode(",", array_keys($fields));
        $query .= ") VALUES (";
        foreach ($fields as $key => $val) {
            $terms[] = $val;
            $qs[] = '?';
        }
        $query .= implode(",", $qs);
        $query .= ")";

        $this->sqlStmt[] = array($query, $terms);
        return array($query, $terms);
    }

    public function buildSelectStatement($table, $fields, IdentityObject $identityObject)
    {
        $query = "SELECT ";
        $query .= implode(',', array_values($fields));
        $query .= " FROM {$table}";
        if(!is_null($this->join)){
            $query .= " JOIN $this->join ";
        }
        $query .= " WHERE ";
        list($que, $terms) = $this->buildWhere($identityObject);
        $query .= $que;
        $this->sqlStmt[] = array($query, $terms);
        return array($query, $terms);
    }

    public function buildDeleteStatement($table, IdentityObject $identityObject){
        $query  = "DELETE FROM {$table} WHERE ";
        list($que, $terms) = $this->buildWhere($identityObject);
        $query .= $que;
        $this->sqlStmt[] = array($query, $terms);
        return array($query, $terms);
    }

    public function buildWhere(IdentityObject $identityObject){
        $cond = array();
        $terms = array();
        foreach ($identityObject->getComps() as $compdata) {
            if (is_array($compdata)) {
                $cond[] = "{$compdata['name']} {$compdata['operator']} ?";
                $terms[] = $compdata['value'];
            } else {
                $cond[] = $compdata;
            }
        }
        $query = implode(" ", $cond);
        return array($query, $terms);
    }

    public function join($value){
        $this->join = $value;
    }

    public function fetchSql(){
        $sql = array();
        foreach ($this->sqlStmt as $key => $val){
            $value = $val[1];
            $sql[] = preg_replace_callback("%\?%", function($match) use($value){
                static $i = 0;
                $i++;
                return str_replace('?', (is_int($value[$i-1]) ? $value[$i-1] :"'{$value[$i-1]}'"),$match[0]);
            }, $val[0]);
        }
        return $sql;
    }
}