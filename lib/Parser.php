<?php

namespace lib;

class Parser {

   /**
    *  The lexer.
    *
    *  @var object
    *  @access protected
    */
    protected $_lexer;

   /**
    *  Tags that may not contain other tags.
    *
    *  @var array
    *  @access protected
    */
    protected $_text_only = array(
        'code',
        'script',
        'textarea',
        'style'
    );

    protected function _lookahead($num) {
        return $this->_lexer->lookahead($num);
    }

   /**
    *  Performs a single token lookahead.
    *
    *  @access protected
    *  @return object
    */
    protected function _peek() {
        return $this->_lookahead(1);
    }

    public function parse() {

    }

}

?>
