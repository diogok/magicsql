<?php

class Connection {

    static function MySql($host,$user,$password,$db){
	    $host = "mysql:host=".$host.";dbname=".$db;     
        $pdo = new PDO($host,$user,$password) ;
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $pdo->isSqlite = false;
        $pdo->db = $db;
        return $pdo;
    }

    static function Sqlite($file){
	    $host = "sqlite:".$file;     
        $pdo = new PDO($host) ;
        $pdo->isSqlite = true ;
        return $pdo;
    }

}

class MagicSql {

    public $joins ;
    public $hasJoin ;
    public $table ;
    public $index;
    public $con ;

    public $getById ;
    public $insert ;
    public $update ;
    public $delete ;
    
    public $max ;

    function __construct($con,$table,$index=null) {
        $this->table = $table ;
        $this->index = $index ;
        $this->con = $con ; 
        $this->hasJoin = false;
        if($this->con->isSqlite) {
            $this->configureSqlite();
        } else{
            $this->configureMySql();
        }
    }

    private function configureMySql() {
        $query = $this->con->query("describe ".$this->table." ");
        while($obj = $query->fetchObject()) {
//           if($obj->Extra != "auto_increment")
                $this->fields[] = $obj->Field ;
           if($obj->Key == "PRI")
                $this->index = $obj->Field ;
        }
    }

    private function configureSqlite() {
        $query = $this->con->query("select *,count(*) from ".$this->table." limit 1");
        $query = $this->con->query("pragma table_info (".$this->table.")");
        while($obj = $query->fetchObject()) {
            $this->fields[] = $obj->name ;
            if($obj->pk == "1")
                $this->index = $obj->name;
        }
    }

    function getNew() {
       $obj  = new StdClass ;
       foreach($this->fields as $f) {
           $obj->$f=  null ;
       }
       return $obj;
    }

    function searchAny($fields,$values,$order=null,$limit=null) {
        return $this->search($fields,$values,$order,$limit,"or");
    }

    function search($fields,$values,$order=null,$limit=null,$op="and") {
        if(is_string($fields) && strpos($fields,",") > 0) {
            $fields = explode(",",$fields);
        }
        if(is_string($values) && strpos($values,",") > 0) {
            $values = explode(",",$values);
        }
        if(!is_array($fields)) {
            $fields = array($fields);
        }
        if(!is_array($values)) {
            $values = array($values);
        }
        $sql = "SELECT * from ".$this->table ;
        $sql .= " where ";
        foreach($fields as $field){
            $where[] = $field." like ?";
        }
        $sql .= implode($where," ".$op." ");
        if($order != null){
            $sql .= " order by ".$order ;
        }
        if($limit != null) {
            $sql .= " limit ".$limit ;
        }
        $sql .= " ;";
        $query = $this->con->prepare($sql);
        $query->execute($values);
        if($this->con->errorcode() != "00000") return $this->sqlError();
        $objs = array();
        while($obj = $query->fetchObject()){
            $objs[] = $obj;
        }
        return $this->returnObject($objs);
    }

    function count($where=null) {
        $sql = "select count(*) from ".$this->table ;
        if($where != null) {
            $sql .= " where ".$where ;
        }
        $r = $this->con->query($sql)->fetchObject();
        if($this->con->errorcode() != "00000") return $this->sqlError();
        foreach($r as $v) {
            $num = $v ;
        }
        return $num ;
    }

    function select($where=null,$order=null,$limit=null,$deep=1) {
        $sql = "select * from ".$this->table ;
        if($where != null) {
            $w = null ;
            if(is_array($where) and count($where) >= 1) {
                $sql .= " where ";
                foreach($where as $field=>$value) {
                    $w .= "AND ".$field." = '".$value."' ";
                }
                $w = substr($w,3);
                $sql .= $w ;
            } else if(is_string($where)) {
                $sql .= " where ".$where ;
            }
        }
        if($order != null) {
            $sql .= " order by ".$order ;
        }
        if($limit != null) {
            $sql .= " limit ".$limit ;
        }
        $query = $this->con->query($sql);
        if($this->con->errorcode() != "00000") return $this->sqlError();
        $objs = null;
        while($obj = $query->fetchObject()) {
            $objs[] = $obj ;
        }
        $ret = $this->returnObject($objs,$deep) ;
        return $ret;
    }

    function getById($id) {
       if(!$this->hasIndexDefined()) return false ;
       if($this->getById == null) {
            $this->getById = $this->con->prepare("select * from ".$this->table." where ".$this->index." = ? ;");
       }
       $this->getById->execute(array($id));
       if($this->con->errorcode() != "00000") return $this->sqlError();
       $objs = $this->getById->fetchObject();
       return $this->returnObject($objs) ;
    }

    function get($field,$value=null,$order=null,$limit=null) {
       if($field == null) return $this->select(null,$order,$limit);
       if($value == null) return $this->getById($field);
       if(is_array($field) or is_array($value) or $order != null or $limit != null) return $this->search($field,$value,$order,$limit);
       $get = $this->con->prepare("select * from ".$this->table." where ".$field." like ? ;");
       $get->execute(array($value));
       if($this->con->errorcode() != "00000") return $this->sqlError();
       $objs = null ;
       while($ob = $get->fetchObject()) {
            $objs[] = $ob ;
       }
       return $this->returnObject($objs) ;
    }

    function delete(&$object) {    
       if (!$this->hasIndexDefined()) return false ;
       if($this->delete == null) {
            $this->delete = $this->con->prepare("delete from ".$this->table." where ".$this->index." = ? ;");
       }
       $k = $this->index ;
       $values[] = $object->$k;
       $this->delete->execute($values);
       if($this->con->errorcode() != "00000") return $this->sqlError();
       $object = null ;
       return true ;
    }

    function update(&$object,$where=null) {
       if(!$this->hasIndexDefined()) return false ;
       if($this->update == null) {
           $sql = "update ".$this->table." set ";
           foreach($this->fields as $key) {
                $fs[] = " $key = ? ";
           }
           $sql .= implode($fs,",");
           $sql .= " where ".$this->index." = ? ;";
           $this->update = $this->con->prepare($sql);
       }
       foreach($this->fields as $k) {
        $values[] = $object->$k;
       }
       $k = $this->index ;
       $values[] = $object->$k;
       $this->update->execute($values);
       if($this->con->errorcode() != "00000") return $this->sqlError();
       return $this->returnObject($object) ;
    }

    function insert(&$object) {
        if($this->insert == null) {
            $sql = "insert into ".$this->table." (";
            $sql .= implode($this->fields,",");
            $sql .= ") values (";
            foreach ($this->fields as $key) {
                    $var[] = "?";
            }
            $sql .= implode($var,",");
            $sql .= ")";
            $this->insert = $this->con->prepare($sql);
       }    
       foreach($this->fields as $key) {
           if($key == $this->index and $object->$key == null) {
               if($this->max == null) {
                   $q = $this->con->query("select max(".$key.") from ".$this->table);
                   $obj = $q->fetchObject() ;
                   foreach($obj as $v) {
                       $this->max = $v;
                   }
               }
               $this->max++;
               $values[] = $this->max ;
           } else {
               $values[] = $object->$key;
           }
       }
       $this->insert->execute($values);
       if($this->con->errorcode() != "00000") return $this->sqlError();
       if($this->insert->errorcode() != "00000") return $this->sqlError($this->insert);
       if($this->index != null and $this->con->lastInsertId() != null) {
           $k = $this->index ;
           $object->$k = $this->con->lastInsertId();
       }
       return $object;
    }

    private function hasIndexDefined() {
        if( $this->index != null )     {
            return true ;
        } else {
            throw new Exception("Index Not Defined in MagicSql");
            return false ;
        }
    }

    private function sqlError($con=null) {
        $con = ($con)?$con:$this->con ;
        var_dump($con->errorinfo());
        throw new Exception("Sql error");
        return false;
    }

    private function returnObject($obj,$deep=2) {
        if($obj == null) return $obj ;
        if(is_array($obj)) {
            $col = new MagicCollection();
            foreach($obj as $v) {
                $col->append($v);
            }
            $obj = $col ;
        }
        if($deep == -1) {
            return $obj;
        }
        if($this->hasJoin === false) return $obj;
        $r = null;
        foreach($this->joins as $j) {
            if( (is_array($obj) and count($obj) >= 1) or $obj instanceof MagicCollection) {
                $r = $this->multiJoin($obj,$j,$deep - 1);
            } else {
                $r = $this->doJoin($obj,$j,$deep - 1);
            }
        }
        return $r ;
    }

    private function multiJoin($arr,$j,$deep){
        $f = $j->foreign;
        $k = $j->key ;
        $t = $j->table ;

        $keys = array();
        foreach($arr as $obj) {
            $key = "'".$obj->$k."'" ;
            if(!in_array($key,$keys)) {
                $keys[] = $key;
            }
            $obj->$t = new MagicCollection();
        }

        $where =  $f." in (".implode(",",$keys).")";
        $joins = $j->db->select($where,null,null,$deep);

        if(count($joins) >= 1 ) {
            foreach($joins as $join) {
                foreach($arr as $obj) {
                    if($join->$f == $obj->$k) {
                        $obj->$t->append($join);
                    }
                }
            }
        }

        return $arr;
    }

    private function doJoin($obj,$j,$deep) {
        $f = $j->foreign;
        $k = $j->key ;
        $t = $j->table ;
        if($j->db != null) {
            $where =  $f." in ('".$obj->$k."')";
            $arr = $j->db->select($where,null,null,$deep);
        }
        $obj->$t = $arr ;
        return $obj ;
    }

    public function configureJoin($table,$foreign,$key,$db=null) {
        $this->hasJoin = true;
        $join = new StdClass ;
        $join->table = $table;
        $join->foreign = $foreign;
        $join->key = $key ;
        $join->db = $db;
        $this->joins[] = $join;
    }
}

class MagicCollection extends ArrayObject {

    public function get($k) {
        return $this->offsetGet($k);
    }

    public function set($k,$v) {
        return $this->offsetSet($k,$v);
    }
}
?>
