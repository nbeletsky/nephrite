<?php

namespace lib;

class Lexer {

    protected $_deferred_tokens = array();

    protected $_indent_stack = array();

    protected $_input;

    protected $_line_no = 1;

    protected $_pipeless = false;

    protected $_stash = array();

    protected function _class_name() {
        return $this->_scan('/^\.([\w-]+)/', 'class');
    }

    protected function _code() {
        $matches = array();
        $pattern = '/^(!?=|-)([^\n]+)/';
        if ( preg_match($pattern, $this->_input, $matches) ) {
            $this->_consume(mb_strlen($matches[0]));
            $flags = $matches[1];
            $token = $this->_tok('code', $matches[2]);
            $token += array(
                'escape' => ( $flags[0]  === '=' ),
                'buffer' => ( $flags[0]  === '=' || $flags[1] === '=' )
            )
            return $token;
        }
    }

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

    protected function _filter() {
        return $this->_scan('/^:(\w+)/', 'filter');
    }

    protected function _id() {
        return $this->_scan('/^#([\w-]+)/', 'id');
    }

    public function lookahead($num) {
        $fetch = $num - count($this->_stash);
        while ( $fetch-- > 0 ) {
            $this->_stash[] = this->_next();
        }
        return $this->_stash[--$num];
    }

    protected function _next() {
        $methods = array(
            '_deferred',
            '_eos',
            '_pipeless_text',
            '_doctype',
            // '_include', // not used
            // '_mixin',   // not used
            '_tag',
            '_filter',
            // '_each',    // not used
            '_code',
            '_id',
            '_class_name',
            '_attrs',
            '_indent',
            '_comment',
            '_colon',
            '_text'
        );

        foreach ( $methods as $method ) {
            $token = $this->$method();
            if ( $token ) {
                return $token;
            }
        }
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

    protected function _scan($regex, $type) {
        $matches = array();
        if ( preg_match($regex, $this->_input, $matches) ) {
            $this->_consume(mb_strlen($matches[0]));
            return $this->_tok($type, $matches[1]);
        }
    }

    protected function _tag() {
        $pattern = '/^(\w[-:\w]*)/';
        $matches = array();
        if ( preg_match($pattern, $this->_input, $matches) ) {
            $this->_consume(mb_strlen($matches[0]));
            $name = $matches[1];
            if ( $name[mb_strlen($name) - 1] == ':' ) {
                $name = mb_substr($name, 0, -1);
                $token = $this->_tok('tag', $name);
                $this->_deferred_tokens[] = $this->_tok(':');
                while ( $this->_input[0] == ' ' ) {
                    $this->_input = mb_substr($this->_input, 1);
                }
            } else {
                $token = $this->_tok('tag', $name);
            }
            return $token;
        }
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
