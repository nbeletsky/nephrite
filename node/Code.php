<?php

namespace node;

class Code extends Node {

    protected $_block;

    protected $_buffer;

    protected $_escape;

    protected $_instrument_line_number;

    protected $_val;

    public function __construct($val, $buffer, $escape) {
        $this->_val = $val;
        $this->_buffer = $buffer;
        $this->_escape = $escape;
        if ( preg_match('/^ *else/', $val) ) {
            $this->_instrument_line_number = false;
        }

    }

    public function get_instrument_line_number() {
        return $this->_instrument_line_number;
    }

    public function set_block($block) {
        $this->_block = $block;
    }

}

?>
