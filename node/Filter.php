<?php

namespace node;

use node\Block;

class Filter {

    protected $_attrs;

    protected $_block;

    protected $_is_AST_filter;

    protected $_line;

    protected $_name;

    public function __construct($name, $block, $attrs) {
        $this->_name = $name;
        $this->_block = $block;
        $this->_attrs = $attrs;
        $this->_is_AST_filter = $block instanceof Block;

    }

    public function set_line($num) {
        $this->_line = $num;
    }
}

?>
