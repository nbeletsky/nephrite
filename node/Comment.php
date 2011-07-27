<?php

namespace node;

class Comment extends Node {

    protected $_buffer;

    protected $_val;

    public function __construct($val, $buffer) {
        $this->_val = $val;
        $this->_buffer = $buffer;
    }

}

?>
