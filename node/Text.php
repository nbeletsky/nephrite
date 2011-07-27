<?php

namespace node;

class Text extends Node {

    protected $_nodes = array();

    public function __construct($line) {
        if ( is_string($line) ) {
            $this->push($line);
        }
    }

    public function get_nodes() {
        return $this->_nodes;
    }

    public function push($node) {
        $this->_nodes[] = $node;
        return $this->_nodes;
    }

}

?>
