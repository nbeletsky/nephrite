<?php

namespace node;

abstract class Node {

    protected $_line;

    public function get_line() {
        return $this->_line;
    }

    public function set_line($num) {
        $this->_line = $num;
    }

}

?>
