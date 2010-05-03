<?php
require_once 'MagicSql.class.php';

class MagicRepository {

    static public $repo ;
    public $dbs ;
    public $tables ;
    public $con ;

    static function getStaticTable($table,$con=null) {
        if(self::$repo == null) {
            self::$repo = new MagicRepository($con);
        }
        foreach($this->dbs as $db) {
            if($db->table == $table) {
                return $db;
            }
        }

    }

    function get($table) {
        foreach($this->dbs as $db) {
            if($db->table == $table) {
                return $db;
            }
        }
        return false;
    }

    function getTable($table) {
        return $this->get($table);
    }

    function table($table) {
        return $this->get($table);
    }

    function __construct($con) {
        $this->con = $con ;
        if($this->con->isSqlite) {
            $this->configureSqlite();
        } else {
            $this->configureMySql();
        }
        $this->configureJoin();
    }

    function configureSqlite() {
        $q = $this->con->query("select * from sqlite_master where type = 'table'");
        while($obj = $q->fetchObject()) {
            $this->tables[] = $obj->name ;
            $this->dbs[] = new MagicSql($this->con,$obj->name);
        }
    }
    
    function configureMySql() {
        $q = $this->con->query("show tables;");
        $db = $this->con->db ;
        $f = "Tables_in_".$db;
        while($obj = $q->fetchObject()) {
            $this->tables[] = $obj->$f ;
            $this->dbs[] = new MagicSql($this->con,$obj->$f);
        }
    }

    function configureJoin(){
        if(count($this->dbs) < 1) return ;
        foreach($this->dbs as $k=>$db) {
            $joins = $this->hasJoin($db);
            if($joins !== false) {
                foreach($joins as $join) {
                    foreach($join as $j) {
                        $this->get($j['table'])->configureJoin($db->table,$j['foreign'],$j['key'],$db);
                    }
                }
            }
        }
    }

    function hasJoin($db) {
        $joins = null;
        foreach($db->fields as $field) {
            $k = $this->isForeign($field);
            if($k !== false) {
                $joins[] = $k;
            }
        }
        if(count($joins) < 1) return false;
        return $joins;
    }

    function isForeign($fd) {
        $fd = strtolower($fd);
        $j = null;
        foreach($this->dbs as $k=>$db) {
           $f = $this->possibleKeys($db);
           foreach($f as $fg) {
                $pfg = strtolower($fg[0]);
                if($pfg === $fd) {
                    $j[] = array("table"=>$fg[2], "foreign"=>$fg[0] ,"key"=> $fg[1]);
                }
            }
        }
        if(count($j) <1) return false;
        return $j ;
    }

    function possibleKeys($db) {
        $f = array();
        foreach($db->fields as $field) {
            $f = array_merge($this->makeKeys($field,$db->table),$f);
        }
        $f = array_merge($this->makeKeys($db->index,$db->table),$f);
        return $f ; 
    }

    function makeKeys($field,$table) {
        $f = array();
        $f[] = array($field."_".$table,$field,$table);
        $f[] = array($table."_".$field,$field,$table);
        $f[] = array($field.$table,$field,$table);
        $f[] = array($table.$field,$field,$table);
        return $f ;
    }

    private function sqlError() {
        var_dump($this->con->errorinfo());
        throw new Exception("Sql error");
        return false;
    }

}
?>
