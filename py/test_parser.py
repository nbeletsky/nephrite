import parser
import unittest

class TestParser(unittest.TestCase):

    def test_doctypes(self):
        self.assertEqual('<?xml version="1.0" encoding="utf-8" ?>', parser.parse('!!! xml'));
        self.assertEqual('<!DOCTYPE html>', parser.parse('doctype html'));
        self.assertEqual('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">', parser.parse('doctype BaSiC'));
        self.assertEqual('<!DOCTYPE html>', parser.parse('!!! 5'));
        self.assertEqual('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', parser.parse('!!!'));
        self.assertEqual('<!DOCTYPE html>', parser.parse('!!! html'));
    
#    def test_line_endings(self):
#        jade = '\n'.join(['p', 'div', 'img']);
#        html = ''.join(['<p></p>','<div></div>','<img/>']);
#        self.assertEqual(html, parser.parse(jade));
#
#        jade = '\r'.join(['p', 'div', 'img']);
#        html = ''.join(['<p></p>','<div></div>','<img/>']);
#        self.assertEqual(html, parser.parse(jade));
#        
#        jade = '\r\n'.join(['p', 'div', 'img']);
#        html = ''.join(['<p></p>','<div></div>','<img/>']);
#        self.assertEqual(html, parser.parse(jade));

#    def test_jade_to_html(self):
#        jade = '!!! 5'
#        jade = parser.parse(jade)
#        html = '<!DOCTYPE html>'
#        self.assertEqual(jade, html)
#
#        jade = 'doctype Basic'
#        jade = parser.parse(jade)
#        html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">'
#        self.assertEqual(jade, html)
#        
#        jade = '''
#html
#'''
#        jade = parser.parse(jade)
#        html = '''
#<html></html>
#'''
#        self.assertEqual(jade, html)


if __name__ == '__main__':
    unittest.main()
