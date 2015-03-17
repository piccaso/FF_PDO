<?php

/**
 * Class FF_PDO
 */
class FF_PDO extends PDO {
    /**
     * For compatibility with SQLITE3
     * @param $sql
     * @return array|bool|mixed
     */
    public function querySingle($sql){
        $r = false;
        $res = $this->query($sql);
        if($res){
            $r = $res->fetch();
            $res->closeCursor();
            if($r !== false AND is_array($r) AND count($r) === 1) $r = current($r);
        }
        return $r;
    }

    /**
     * @param $cnt
     * @param $all_args
     * @return array
     */
    private function build_args($cnt,$all_args){
        if(is_array($all_args)) {
            if($cnt == 2 && is_array($all_args[1])){
                return $all_args[1];
            }
            if ($cnt > 1) {
                array_shift($all_args);
                return ($all_args);
            }
        }
        return array();
    }

    /**
     * @param $column_name
     * @return mixed
     */
    private function is_valid_column_name($column_name){
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/',$column_name);
    }

    private function into($what,$table,$data){
        if(!$this->is_valid_column_name($table)) return false;
        $columns = array();
        $keys_colon = array();
        $data_colon = array();
        foreach($data as $key=>$value){
            if(!$this->is_valid_column_name($key)) return false;
            $columns[]=$key;
            $key = ':'.$key;
            $keys_colon[]=$key;
            $data_colon[$key]=$value;
        }
        $sql = "$what $table (".implode(', ',$columns).") VALUES (".implode(', ',$keys_colon).")";
        $ps = $this->prepare($sql);
        return $ps->execute($data_colon);
    }

    public function replace($table,$data){
        return $this->into('REPLACE INTO',$table,$data);
    }

    /**
     * @param $sql
     * @param null $args
     * @return mixed
     */
    public function q($sql,$args=null){
        $_func_num_args = func_num_args();
        $_func_get_args = func_get_args();
        $args = $this->build_args($_func_num_args,$_func_get_args);
        $ps = $this->prepare($sql);
        if($ps->execute($args)){
            $r = $ps->fetch();
            if($ps->columnCount() === 1 && is_array($r)){
                $r=current($r);
            }
            $ps->closeCursor();
            return $r;
        }
    }

    /**
     * @param $sql
     * @param null $args
     * @return array
     */
    public function qt($sql,$args=null){
        $_func_num_args = func_num_args();
        $_func_get_args = func_get_args();
        $args = $this->build_args($_func_num_args,$_func_get_args);
        $ps = $this->prepare($sql);
        if($ps->execute($args)){
            $r = $ps->fetchAll();
            $ps->closeCursor();
            return $r;
        }
    }

    /**
     * @param $sql
     * @param null $args
     * @return array
     */
    public function qv($sql,$args=null){
        $_func_num_args = func_num_args();
        $_func_get_args = func_get_args();
        $args = $this->build_args($_func_num_args,$_func_get_args);
        $ps = $this->prepare($sql);
        if($ps->execute($args)){
            $r = array();
            while($row = $ps->fetch()){
                $r[]=current($row);
            }
            $ps->closeCursor();
            return $r;
        }
    }
}






















