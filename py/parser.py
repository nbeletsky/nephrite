import os
import re

def parse(input):
    '''Parses a file.

    Keyword arguments:
    input -- the file

    Returns: string

    '''

    if os.path.isfile(input):
        with open(input, encoding='utf-8') as file:
            return ''.join([detect_token(line) for line in file])
    else: 
        return ''.join([detect_token(line) for line in input.splitlines()])

def detect_token(line):
    # Doctype
    return parse_doctype(line) \
        or parse_tag(line) \
        or ''

def parse_doctype(line):
    if not line.startswith('!!!') and not line.startswith('doctype'):
        return False

    doctypes = {
        '5': '<!DOCTYPE html>',
        'html': '<!DOCTYPE html>',
        'xml': '<?xml version="1.0" encoding="utf-8" ?>',
        'default': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'transitional': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic': '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile': '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    }

    if line == '!!!':
        return doctypes['5']

    left, right = line.split(' ', 1)
    type = right.strip().lower() 
    return doctypes.get(type, doctypes['5'])

def parse_tag(line):
    match = re.search('^(\w[-:\w]*)', line)
    if not match:
        return False
    return match.group(1)
