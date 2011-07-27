<?php

namespace lib;

class Lexer {

    protected $_deferred_tokens = array();

    protected $_indent_re = null;

    protected $_indent_stack = array();

    protected $_input;

    protected $_last_indents = 0;

    protected $_line_no = 1;

    protected $_pipeless = false;

    protected $_stash = array();

    public function __construct($str) {
        $this->_input = preg_replace('/\r\n|\r/g', "\n", $str);
    }

    public function advance() {
        return $this->_stashed() || $this->_next();
    }

    protected function _attrs() {
        $index = 0;
        $length = 0;
        $token = array();
        $states = array();
        $key = '';
        $val = '';

        if ( $this->_input[0] == '(' ) {
            $index = $this->_get_delimiter_index('(', ')');
            $str = mb_substr($this->_input, 1, $index - 1);
            $token = $this->tok('attrs');
            $length = mb_strlen($str);
            $states = array('key');
        }

        $this->_consume($index + 1);
        $token += array('attrs' => array());

        $parse = function($char) use (&$token, &$states, &$key, &$val) {
            $quote = '';
            switch ( $char ) {
                case ',':
                case "\n":
                    switch ( end($states) ) {
                        case 'expr':
                        case 'array':
                        case 'string':
                        case 'object':
                            $val .= $char;
                            break;
                        default:
                            $states[] = 'key';
                            $val = trim($val);
                            $key = trim($key);
                            if ( $key == '' ) {
                                return false;
                            }
                            $sub = preg_replace('/^[\'"]|[\'"]$/g', '', $key);
                            $token['attrs'][$sub] = ( $val == '' )
                                ? true
                                : $this->_interpolate($val, $quote);
                            break;
                    }
                    break;
                case '=':
                    switch ( end($states) ) {
                        case 'key char':
                            $key .= $char;
                            break;
                        case 'val':
                        case 'expr':
                        case 'array':
                        case 'string':
                        case 'object':
                            $val .= $char;
                            break;
                        default:
                            $states[] = 'val';
                            break;
                    }
                    break;
                case '(':
                    if ( end($states) == 'val' ) {
                        $states[] = 'expr';
                    }
                    $val .= $char;
                    break;
                case ')':
                    if ( end($states) == 'expr' ) {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;
                case '{':
                    if ( end($states) == 'val' ) {
                        $states[] = 'object';
                    }
                    $val .= $char;
                    break;
                case '}':
                    if ( end($states) == 'object' ) {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;
                case '[':
                    if ( end($states) == 'val' ) {
                        $states[] = 'array';
                    }
                    $val .= $char;
                    break;
                case ']':
                    if ( end($states) == 'array' ) {
                        array_pop($states);
                    }
                    $val .= $char;
                    break;
                case '"':
                case "'":
                    switch ( end($states) ) {
                        case 'key':
                            $states[] = 'key char';
                            break;
                        case 'key char':
                            array_pop($states);
                            break;
                        case 'string':
                            if ( $quote == $char ) {
                                array_pop($states);
                            }
                            $val .= $char;
                            break;
                        default:
                            $states[] = 'string';
                            $val .= $char;
                            $quote = $char;
                    }
                    break;
                case '':
                    break;
                default:
                    switch ( end($states) ) {
                        case 'key':
                        case 'key char':
                            $key .= $char;
                            break;
                        default:
                            $val .= $char;
                    }
                    break;
            }
        };

        for ( $i = 0; $i < $length; ++$i ) {
            $parse($str[$i]);
        }

        $parse(',');

        return $token;
    }

    protected function _class_name() {
        return $this->_scan('/^\.([\w-]+)/', 'class');
    }

    protected function _code() {
        $pattern = '/^(!?=|-)([^\n]+)/';
        if ( !preg_match($pattern, $this->_input, $matches) ) {
            return false;
        }

        $this->_consume(mb_strlen($matches[0]));
        $flags = $matches[1];
        $token = $this->tok('code', $matches[2]);
        $token += array(
            'escape' => ( $flags[0]  === '=' ),
            'buffer' => ( $flags[0]  === '=' || $flags[1] === '=' )
        )
        return $token;
    }

    protected function _colon() {
        return $this->_scan('/^: */', ':');
    }

    protected function _comment() {
        $pattern = '/^ *\/\/(-)?([^\n]*)/';
        if ( !preg_match($pattern, $this->_input, $matches) ) {
            return false;
        }

        $this->_consume(mb_strlen($matches[0]));
        $token = $this->tok('comment', $matches[2]);
        $token += array(
            'buffer' => ( $matches[1] != '-' )
        );
        return $token;
    }

    protected function _consume($length) {
        $this->_input = mb_substr($this->_input, $length);
    }

    public function defer($token) {
        $this->_deferred_tokens[] = $token;
    }

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
            return $this->tok('outdent');
        }

        return $this->tok('eos');
    }

    protected function _filter() {
        return $this->_scan('/^:(\w+)/', 'filter');
    }

    protected function _get_delimiter_index($start, $end) {
        $start_num = $end_num = $pos = 0;
        $length = mb_strlen($this->_input);
        for ( $i = 0; $i < $length; ++$i ) {
            if ( $this->_input[$i] == $start ) {
                ++$start_num;
            } elseif ( $this->_input[$i] == $end && $start_num == ++$end_num ) {
                return $i;
            }
        }
        return $pos;
    }

    public function get_line_no() {
        return $this->_line_no;
    }

    protected function _id() {
        return $this->_scan('/^#([\w-]+)/', 'id');
    }

    protected function _include() {
        return $this->_scan('/^include +([^\n]+)/', 'include');
    }

    protected function _indent() {
        if ( $this->_indent_re ) {
            preg_match($this->_indent_re, $this->_input, $matches);
        } else {
            // Tabs
            $pattern = '/^\n(\t*) */';
            preg_match($pattern, $this->_input, $matches);

            // Spaces
            if ( isset($matches[0]) && !isset($matches[1]) ) {
                $pattern = '/^\n( *)/';
                preg_match($pattern, $this->_input, $matches);
            }

            if ( isset($matches[0]) && isset($matches[1]) ) {
                $this->_indent_re = $pattern;
            }
        }

        if ( count($matches) ) {
            $indents = mb_strlen($matches[1]);

            ++$this->_line_no;
            $this->_consume($indents + 1);

            if ( $this->_input[0] == ' ' || $this->_input == "\t" ) {
                throw new \Exception('Invalid indentation.'
                    . '  You can use tabs or spaces, but not both.');
            }

            // Blank line
            if ( $this->_input[0] == "\n" ) {
                return $this->tok('newline');
            }

            // Outdent
            if ( count($this->_indent_stack) && $this->_indent_stack[0] > $indents ) {
                while ( count($this->_indent_stack) && $indents < $this->_indent_stack[0]) {
                    $this->_stash[] = $this->tok('outdent');
                    array_shift($this->_indent_stack);
                }
                $token = array_pop($this->_stash);
            } elseif ( $indents && $this->_indent_stack[0] != $indents ) {
                array_unshift($this->_indent_stack, $indents);
                $token = $this->tok('indent', $indents);
            } else {
                $token = $this->tok('newline');
            }

            return $token;
        }
    }

    protected function _interpolate($attr, $quote) {
        return preg_replace('/#\{([^}]+)\}/g', function($match, $expr) use ($quote) {
            return $quote . ' + (' . $expr . ') + ' . $quote;
        });
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
             '_include',
            // '_mixin',   // not implemented
            '_tag',
            // '_filter',  // not implemented
            // '_each',    // not implemented
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
        if ( !$this->_pipeless || mb_substr($this->_input, 0, 1) == "\n" ) {
            return false;
        }

        $index = mb_strpos($this->_input, "\n");
        if ( $index === false ) {
            $index = mb_strlen($this->_input);
        }
        $to_consume = mb_substr($this->_input, 0, $index);
        $this->_consume(mb_strlen($to_consume));
        return $this->tok('text', $to_consume);
    }

    protected function _scan($regex, $type) {
        if ( !preg_match($regex, $this->_input, $matches) ) {
            return false;
        }

        $this->_consume(mb_strlen($matches[0]));
        return $this->tok($type, $matches[1]);
    }

    protected function set_pipeless($val) {
        $this->_pipeless = $val;
    }

    protected function _stashed() {
        return ( count($this->_stash) && array_shift($this->_stash) );
    }

    protected function _tag() {
        $pattern = '/^(\w[-:\w]*)/';
        if ( !preg_match($pattern, $this->_input, $matches) ) {
            return false;
        }

        $this->_consume(mb_strlen($matches[0]));
        $name = $matches[1];
        if ( $name[mb_strlen($name) - 1] == ':' ) {
            $name = mb_substr($name, 0, -1);
            $this->_deferred_tokens[] = $this->tok(':');
            $this->_input = ltrim($this->_input);
        }

        $token = $this->tok('tag', $name);
        return $token;
    }

    protected function _text() {
        return $this->_scan('/^(?:\| ?)?([^\n]+)/', 'text');
    }

    public function tok($type, $val) {
        return array(
            'type' => $type,
            'line' => $this->_line_no,
            'val'  => $val
        );
    }

}

?>
