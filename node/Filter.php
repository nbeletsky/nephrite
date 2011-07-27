<?php

namespace node;

use node\Block;

class Filter extends Node {

    protected $_attrs;

    protected $_block;

    protected $_is_AST_filter;

    protected $_name;

    public function __construct($name, $block, $attrs) {
        $this->_name = $name;
        $this->_block = $block;
        $this->_attrs = $attrs;
        $this->_is_AST_filter = $block instanceof Block;

    }

}

?>
