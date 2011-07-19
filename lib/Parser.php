<?php

namespace lib;

use node\Block;

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

    protected function _accept($type) {
        if ( $type = {$this->_peek()}['type'] ) {
            return $this->_advance();
        }
    }

    protected function _advance() {
        return $this->_lexer->advance();
    }

    protected function _expect($type) {
        if ( $type = {$this->_peek()}['type'] ) {
            return $this->_advance();
        }
        throw new \Exception('Expected "' . $type . '", but got "'
            . {$this->_peek()}['type'] . '".');
    }

    protected function _get_line_no() {
        return $this->_lexer->get_line_no();
    }

    protected function _lookahead($num) {
        return $this->_lexer->lookahead($num);
    }

    public function parse() {
        $block = new Block();
        $block->set_line($this->_get_line_no());
        while ( {$this->_peek()}['type'] != 'eos' ) {
            if ( {$this->_peek()}['type'] == 'newline' ) {
                $this->_advance();
            } else {
                $block->push($this->_parse_expr);
            }
        }

        return $block;
    }

    protected function _parse_AST_filter() {
        $token = $this->_expect('tag');
        $attrs = $this->_accept('attrs');

        $this->_expect(':');
        $block = $this->_parse_block();

        $node = new Filter($token['val'], $block, $attrs && $attrs['attrs']);
        $node->set_line($this->_get_line_no());

        return $node;
    }

    protected function _parse_block() {
        $block = new Block();
        $block->set_line($this->_get_line_no());
        $this->_expect('indent');
        while ( {$this->_peek()}['type'] != 'outdent' ) {
            if ( {$this->_peek()}['type'] == 'newline' ) {
                $this->_advance();
            } else {
                $block->push($this->_parse_expr());
            }
        }
        $this->_expect('outdent');
        return $block;
    }

    protected function _parse_expr() {
        switch ( {$this->_peek()}['type'] ) {
            case 'tag':
                return $this->_parse_tag();
            // case 'mixin': // not implemented
                // return $this->_parse_mixin;
            case 'include':
                return $this->_parse_include();
            case 'doctype':
                return $this->_parse_doctype();
            case 'filter':
                return $this->_parse_filter();
            case 'comment':
                return $this->_parse_comment();
            case 'text':
                return $this->_parse_text();
            // case 'each': // not implemented
                // return $this->_parse_each();
            case 'code':
                return $this->_parse_code();
            case 'id':
            case 'class':
                $token = $this->_advance();
                $this->_lexer->defer($this->_lexer->tok('tag', 'div'));
                $this->_lexer->defer($token);
                return $this->_parse_expr();
            default:
                throw new \Exception('Unexpected token: "'
                    . {$this->_peek()}['type'] . '"');
        }
    }

    protected function _parse_tag() {
        $i = 2;
        if ( {$this->_lookahead($i)}['type'] == 'attrs' ) {
            $i++;
        }
        if ( {$this->_lookahead($i)}['type'] == ':' ) {
            if ( {$this->_lookahead($++i)}['type'] == 'indent' ) {
                return $this->_parse_AST_filter();
            }
        }

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

}

?>
