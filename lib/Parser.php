<?php

namespace lib;

use node\Block;

class Parser {

    protected $_filename;

    protected $_input;

   /**
    *  The lexer.
    *
    *  @var object
    *  @access protected
    */
    protected $_lexer;

    protected $_spaces;

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

    public function __construct($str, $filename) {
        $this->_input = $str;
        $this->_lexer = new Lexer($str);
        $this->_filename = $filename;
    }

    protected function _accept($type) {
        if ( $type == {$this->_peek()}['type'] ) {
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

    protected function _parse_code() {
        $token = $this->_expect('code');
        $node = new Code($token['val'], $token['buffer'], $token['escape']);
        $node->set_line($this->_get_line_no());
        if ( {$this->_peek()}['type'] == 'indent' ) {
            $node->set_block($this->_parse_block());
        }
        return $node;
    }

    protected function _parse_comment() {
        $token = $this->_expect('comment');
        if ( {$this->_peek()}['type'] == 'indent' ) {
            $node = new BlockComment($token['val'], $this->_parse_block(), $token['buffer']);
        } else {
            $node = new Comment($token['val'], $token['buffer']);
        }

        $node->set_line($this->_get_line_no());

        return $node;
    }

    protected function _parse_doctype() {
        $token = $this->_expect('doctype');
        $node = new Doctype($token['val']);
        $node->set_line($this->_get_line_no());
        return $node;
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
            // case 'filter': // not implemented
                // return $this->_parse_filter();
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

    protected function _parse_include() {
        if ( !$this->_filename ) {
            throw new \Exception('The "filename" option is required to use includes.');
        }

        $path = trim($this->_expect('include')) . '.jade';
        $dir = dirname($this->_filename);
        $path = $dir . $path;

        $str = file_get_contents($path);
        $parser = new Parser($str, $path);
        $ast = $parser->parse();

        return $ast;
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

        $name = {$this->_advance()}['val'];
        $tag = new Tag($name);

        $tag->set_line($this->get_line_no());

        while ( true ) {
            switch ( {$this->_peek()}['type'] ) {
                case 'id':
                case 'class':
                    $token = $this->_advance();
                    $tag->set_attribute($token['type'], "'" . $token['val'] . "'");
                    break;
                case 'attrs':
                    $obj = {$this->_advance()}['attrs'];
                    $names = array_keys($obj);
                    foreach ( $names as $name ) {
                        $tag->set_attribute($name, $obj[$name]);
                    }
                    break;
                default:
                    break 2;
            }
        }

        // Check immediate '.'
        if ( {$this->_peek()}['val'] == '.') {
            $tag->set_text_only(true);
            $this->_advance();
        }

        // (text | code | ':')?
        switch ( {$this->_peek()}['type'] ) {
            case 'text':
                $tag->set_text($this->_parse_text());
                break;
            case 'code':
                $tag->set_code($this->_parse_code());
                break;
            case ':':
                $this->_advance();
                $block = new Block();
                $block->push($this->_parse_tag());
                $tag->set_block($block);
        }

        // newline*
        while ( {$this->_peek()}['type'] == 'newline' ) {
            $this->_advance();
        }

        $tag->set_text_only(
            $tag->get_text_only()
            || in_array($tag->get_name(), $this->_text_only)
        );

        if ( $tag->get_name() == 'script' ) {
            $type = $tag->get_attribute('type');
            $pattern = '/^[\'"]|[\'"]$/g';
            if ( $type && preg_replace($pattern, '', $type) != 'text/javascript' ) {
                $tag->set_text_only(false);
            }
        }

        if ( {$this->_peek()}['type'] == 'indent' ) {
            if ( $tag->get_text_only() ) {
                $this->_lexer->set_pipeless(true);
                $tag->set_block($this->_parse_text_block());
                $this->_lexer->set_pipeless(false);
            } else {
                $block = $this->_parse_block();
                if ( $tag_block = $tag->get_block() ) {
                    foreach ( $block->get_nodes() as $node ) {
                        $tag_block->push($node);
                    }
                } else {
                    $tag_block = $block;
                }
                $tag->set_block($tag_block);
            }
        }

        return $tag;
    }

    protected function _parse_text() {
        $token = $this->_expect('text');
        $node = new Text($tok['val']);
        $node->set_line($this->get_line_no());
    }

    protected function _parse_text_block() {
        $text = new Text();
        $text->set_line($this->_get_line_no());
        $spaces = {$this->_expect('indent')}['val'];
        if ( $this->_spaces == null ) {
            $this->_spaces = $spaces;
        }
        $indent = $spaces - $this->_spaces + 1;
        while ( {$this->_peek()}['type'] != 'outdent' ) {
            switch ( {$this->_peek()}['type'] ) {
                case 'newline':
                    $text->push("\n");
                    $this->_advance();
                    break;
                case 'indent':
                    $text->push("\n");
                    $text_block = $this->_parse_text_block();
                    foreach ( $text_block->get_nodes() as $node ) {
                        $text->push($node);
                    }
                    $text->push("\n");
                    break;
                default:
                    $text->push($indent . {$this->_advance()}['val']);
            }
        }

        if ( $this->_spaces == $spaces ) {
            $this->_spaces = null;
        }

        $this->_expect('outdent');
        return $text;
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
