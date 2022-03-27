<?php


class Database {
    public $database_info = array();
    protected $is_able = FALSE;
    protected $PDO = NULL;

    public function __construct( $config ) {
        $this->database_info = $config;
        $this->PDO = $this->initPDO();
        if( $this->PDO !== NULL ) $this->is_able = TRUE;
    }

    protected function initPDO() {
        if( !empty( $this->database_info ) ) {
            $t = $this->database_info;
            try{
                $tPDO = new PDO( "$t[driver]:host=$t[host];dbname=$t[database]", $t['username'], $t['password'] );
                $tPDO->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                $tPDO->exec("set names utf8");
                return $tPDO;
            }
            catch( PDOException $e ) {
                return NULL;
            }
        }
    }

    public function isAble() {
        return $this->is_able;
    }

    public function query( $query, $params = array() ) {
        if( $this->is_able ) {
            try {
                $handler = $this->PDO->prepare($query);
                $handler->execute($params);
                return $handler;
            } catch (PDOException $e) {
                throw  $e;
            }
        }
        else return FALSE;
    }

    public function exec( $sql ) {
        if( $this->is_able ) {
            try {
                $this->PDO->exec($sql);
            } catch (PDOException $e) {
                throw $e;
            }
        }
    }

    public function getError() {
        $info = $this->PDO->errorInfo();
        return implode(':', $info);
    }
}