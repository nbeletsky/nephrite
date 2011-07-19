<?php

namespace node;

class Block {

    protected $_line;

    protected $_nodes = array();

    public function set_line($num) {
        $this->_line = $num;
    }

    public function push($node) {
        $this->_nodes[] = $node;
    }

    public function unshift($node) {
        array_unshift($this->_nodes, $node);
    }

}

?>
