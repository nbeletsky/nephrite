<?php

namespace node;

class BlockComment extends Node {

    protected $_block;

    protected $_buffer;

    protected $_val;

    public function __construct($val, $block, $buffer) {
        $this->_val = $val;
        $this->_block = $block;
        $this->_buffer = $buffer;
    }

}

?>
