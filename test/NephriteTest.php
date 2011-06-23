<?php

class NephriteTest extends \PHPUnit_Framework_TestCase {    

    protected $_nephrite;

    public function setUp() {
        $this->_nephrite = new Nephrite();
    }

    protected function _render($value, $options = array()) {
        return $this->_nephrite->render($value, $options);
    }

    public function test_RenderTags_ReturnsValidHTML() {

        $jade = <<<Jade
html
Jade;
        $html = <<<HTML
<html></html>
HTML;
        $this->assertEquals($html, $this->_render($jade));

        $jade = <<<Jade
html
  body
    p
Jade;
        $html = <<<HTML
<html>
  <body>
  <p></p>
  </body>
</html>
HTML;
        $this->assertEquals($html, $this->_render($jade));

    } 
}

?>
