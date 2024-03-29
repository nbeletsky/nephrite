<?php

namespace node;

class Block extends Node {

    protected $_nodes = array();

    public function get_nodes() {
        return $this->_nodes;
    }

    public function push($node) {
        $this->_nodes[] = $node;
    }

    public function unshift($node) {
        array_unshift($this->_nodes, $node);
    }

}

?>
