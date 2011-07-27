<?php

namespace lib;

class Compiler {

    protected $_buffer;

    protected $_debug = false;

    protected $_doctype;

    protected $_doctypes = array(
        '5'            => '<!DOCTYPE html>',
        'html'         => '<!DOCTYPE html>',
        'xml'          => '<?xml version="1.0" encoding="utf-8" ?>',
        'default'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1'          => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile'       => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    );

    protected $_has_compiled_doctype = false;

    protected $_has_compiled_tag = false;

    protected $_indents = 0;

    protected $_inline_tags = array(
        'a',
        'abbr',
        'acronym',
        'b',
        'br',
        'code',
        'em',
        'font',
        'i',
        'img',
        'ins',
        'kbd',
        'map',
        'samp',
        'small',
        'span',
        'strong',
        'sub',
        'sup'
    );

    protected $_node;

    protected $_options;

    protected $_pp = false;

    protected $_self_closing = array(
        'meta',
        'img',
        'link',
        'input',
        'area',
        'base',
        'col',
        'br',
        'hr'
    );

    protected $_terse;

    protected $_xml;

    public function __construct($node, $options = array()) {
        $this->_options = $options;
        $this->_node = $node;
        $this->_pp = $options['pretty'] || false;
        $this->_debug = ( $options['compile_debug'] !== false );
        if ( $options['doctype'] ) {
            $this->_set_doctype($options['doctype']);
        }
    }

    protected function _buffer($str, $esc) {
        if ( $esc ) {
            $str = htmlentities($str, ENT_QUOTES);
        }
        $this->_buffer[$str];
    }

   /**
    *  Returns the classname without the namespace.
    *
    *  @param object|string $obj          The object or class name from which to retrieve the classname.
    *  @access public
    *  @return string
    */
    protected function _classname_only($obj) {
        if ( !is_object($obj) && !is_string($obj) ) {
            return false;
        }

        $class = explode('\\', ( is_string($obj) ) ? $obj : get_class($obj));
        return array_pop($class);
    }

    public function compile() {
        $this->_buffer = array('var interp;');
        $this->_visit($this->_node);
        return implode("\n", $this->_buffer);
    }

    protected function _line($node) {
        if ( method_exists($node, 'get_instrument_line_number') ) {
            if ( $node->get_instrument_line_number() ) {
                return false;
            }
        }
        $this->_buffer[] = '__.lineno = ' . $node->get_line() . ';';
    }

    protected function _set_doctype($name = 'default') {
        $doctype = $this->_doctypes[strtolower($name)];
        if ( !$doctype ) {
            throw new \Exception('Unknown doctype "' . $name . '".');
        }
        $this->_doctype = $doctype;
        $this->_terse = ( $name == '5' || $name == 'html');
        $this->_xml = ( strpos($doctype, '<?xml') === 0 );
    }

    protected function _visit($node) {
        if ( $this->_debug ) {
            $this->_line($node);
        }
        return $this->_visit_node($node);
    }

    protected function _visit_block($block) {
        foreach ( $block->get_nodes() as $node ) {
            $this->_visit($node);
        }
    }

    protected function _visit_doctype($doctype) {
        if ( $doctype && ($doctype['val'] || !$this->_doctype) ) {
            $this->_set_doctype($doctype['val'] || 'default');
        }

        if ( $this->_doctype ) {
            $this->_buffer($this->_doctype);
        }
        $this->_has_compiled_doctype = true;
    }

    protected function _visit_node($node) {
        $name = strtolower($this->_classname_only($node));
        $method = '_visit_' . $name;
        return $this->$method($node);
    }

}

?>
