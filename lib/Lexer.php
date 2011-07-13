<?php

namespace lib;

class Lexer {

    protected $_deferred_tokens = array();

    protected $_indent_stack = array();

    protected $_input;

    protected $_line_no = 1;

    protected $_pipeless = false;

    protected $_stash = array();

    protected function _consume($length) {
        $this->_input = mb_substr($this->_input, $length);
    }

    protected function _deferred() {
        return array_shift($this->_deferred_tokens);
    }

    protected function _doctype() {
        return $this->_scan('/^(?:!!!|doctype) *(\w+)?/', 'doctype');
    }

    protected function _eos() {
        if ( mb_strlen($this->_input) ) {
            return false;
        }
        if ( count($this->_indent_stack) ) {
            array_shift($this->_indent_stack);
            return $this->_tok('outdent');
        }

        return $this->_tok('eos');
    }

    public function lookahead($num) {
        $fetch = $num - count($this->_stash);
        while ( $fetch-- > 0 ) {
            $this->_stash[] = this->next();
        }
        return $this->_stash[--$num];
    }

    protected function _next() {
        return this->_deferred()
            || this->_eos()
            || this->_pipelessText()
            || this->_doctype()
            || this->_tag()
            || this->_filter()
            || this->_each()
            || this->_code()
            || this->_id()
            || this->_className()
            || this->_attrs()
            || this->_indent()
            || this->_comment()
            || this->_colon()
            || this->_text();
    }

    protected function _pipeless_text() {
        if ( !$this->_pipeless ||  mb_substr($this->_input, 0, 1) == "\n" ) {
            return false;
        }

        $index = mb_strpos($this->_input, "\n");
        if ( $index === false ) {
            $index = mb_strlen($this->_input);
        }
        $to_consume = mb_substr($this->_input, 0, $index);
        $this->_consume(mb_strlen($to_consume));
        return $this->_tok('text', $to_consume);
    }

    protected function _tok($type, $val) {
        return array(
            'type' => $type,
            'line' => $this->_line_no,
            'val'  => $val
        );
    }

}

?>
