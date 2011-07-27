<?php

namespace node;

class Tag extends Node {

    protected $_attrs;

    protected $_block;

    protected $_code;

    protected $_name;

    protected $_text;

    protected $_text_only;

    public function __construct($name, $block = null) {
        $this->_name = $name;
        $this->_attrs = array();
        $this->_block = ( $block ) ?: new Block();
    }

    public function get_attribute($name) {
        return $this->_attrs[$name];
    }

    public function get_block() {
        return $this->_block;
    }

    public function get_name() {
        return $this->_name;
    }

    public function get_text_only() {
        return $this->_text_only;
    }

    public function remove_attribute($name) {
        unset($this->_attrs[$name]);
    }

    public function set_attribute($name, $val) {
        $this->_attrs[$name] = $val;
        return $this;
    }

    public function set_block($block) {
        $this->_block = $block;
    }

    public function set_code($code) {
        $this->_code = $code;
    }

    public function set_text($text) {
        $this->_text = $text;
    }

    public function set_text_only($val) {
        $this->_text_only = $val;
    }
}

?>
