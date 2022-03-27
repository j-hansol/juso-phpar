<?php


class CommandLineParse {
    private $is_able = false;
    private $options = [];

    function __construct() {
        global $argc, $argv;
        if( isset( $argc ) && isset( $argv ) ) {
            $this->is_able = true;
            $this->_parse( $argc, $argv );
        }
    }

    private function _parse( $argc, $argv ) {
        $i = 0;
        if( $argc <= 1 ) return;
        while( $i < $argc ) {
            $t = $argv[$i];
            $v = $argv[$i+1];
            if( preg_match('/^-([^-]+)$/', $t, $matched ) ) {
                $key = $matched[1];
                if( preg_match('/^-([^-]+)$/', $argv[$i+1]) ) {
                    $this->options[$key] = null;
                    $i++;
                }
                else {
                    $this->options[$key] = $v;
                    $i += 2;
                }
            }
            else $i++;
        }
    }

    public function get( $key ) {
        if( !$this->is_able ) return null;
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }
}