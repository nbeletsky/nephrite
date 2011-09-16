import os
import re
from pyparsing import *

grammar = '''
<line> :: <tag> | <comment> | <doctype> | <code> | <include> | <mixin>

<tag>     :: ( <div> | <alpha> ) [ <attribute> ] [ <tag-text> ] | <text-only>
<comment> :: <buffered-comment> | <unbuffered-comment> | <block-comment> | <conditional-comment>
<doctype> :: ( "!!!" | "doctype" ) [ "5" | "html" | "xml" | "default" | "transitional" | "strict" | "frameset" | "1.1" | "basic" | "mobile" ]
<code>    :: ( <interpolation> | <php> )
<include> :: "include " <char>
<mixin>   :: <mixin-define> || <mixin-include>


<div>   :: "div" [ <div_id> ] [ <div_class>+ ]  | <div_id> | <div_class>+
<div_id>    :: "#" <alphanum> [ <extra> ]
<div_class> :: "." <alphanum> [ <extra> ]

<alphanum>   :: <alpha> | <digit>
<alpha>      :: <upper-case> | <lower-case>
<upper-case> :: "A" | ... | "Z"
<lower-case> :: "a" | ... | "z"
<digit>      :: "0" | ... | "9"
<extra>      :: "-" | "_"

<attribute>  :: "(" <key-value> | <text> ")" [ "," ]
<key-value>  :: <text> "=" ( '"' <text> '"' | "'" <text> "'" )

<tag-text> :: <text> | ":" ( <div> | <alpha> ) <text> | <indent> <text-block> | <indent> <tag>
<text>     :: <char> [ <interpolation> ]

<text-block> :: "| " <text>
<char>       :: <any US-ASCII character (octets 0 - 127)>

<interpolation>        :: <output-interpolation> | <escape-interpolation>
<output-interpolation> :: "{{" <variable> "}}"
<variable>             :: "$" ( <alphanum> | "_" )
<escape-interpolation> :: "\" <output-interpolation>

<text-only>      :: ( <text-only-tags> [ <attribute> ] | ( <div> | <alpha> ) [ <attribute> ] "." ) <indent> <text>
<text-only-tags> :: "code" | "script" | "textarea" | "style" | "title"

<indent> :: "\n" <tab>
<tab>    :: "\t" | "  "

<buffered-comment>    :: "//" <char>
<unbuffered-comment>  :: "//-" <char>
<block-comment>       :: "//" <indent> <tag>
<conditional-comment> :: "//if " <char> <indent> <tag>

<php>       :: "- " ( <char> | <if> | <elseif> | <else> | <while> | <for> | <foreach> | <switch> | <case> )
<if>        :: "if " <statement> <indent>
<elseif>    :: "else" <if> <indent>
<else>      :: "else" <indent>
<while>     :: "while " <statement> <indent>
<for>       :: "for " <statement> <indent>
<foreach>   :: "foreach" <statement> <indent>
<switch>    :: "switch" <statement> <indent>
<case>      :: "case " <char> | "default"
<statement> :: "(" <char> ")"

<mixin-define>  :: "mixin " <text> <indent> <line>
<mixin-include> :: "mixin " <text>
'''

def parse(input):
    '''Parses a file.

    Keyword arguments:
    input -- the file

    Returns: string

    '''

    if os.path.isfile(input):
        with open(input, encoding='utf-8') as file:
            return detect_token(file.read())
    else:
        return detect_token(input)

def detect_token(jade):

    char = Word(printables)
    variable = Literal('$') + Word(alphanums + '_')

    output_interpolation = Literal('{{') + variable + Literal('}}')
    escape_interpolation = Literal('\\') + output_interpolation
    interpolation = output_interpolation | escape_interpolation
    text = char + ZeroOrMore(interpolation)

    text_block = Literal("| ") + text

    doctype = oneOf('!!! doctype') + Optional(oneOf('5 html xml default' \
            + 'transitional strict frameset 1.1 basic mobile', True))
    doctype.setResultsName('doctype')
    doctype.setParseAction(parse_doctype)

    extra = ZeroOrMore('-') + ZeroOrMore('_')

    div_id = Literal('#') + Word(alphanums + extra)
    div_id.setResultsName('div_id')
    div_class = Literal('.') + Word(alphanums + extra)
    div_class.setResultsName('div_class')

    div = (Suppress('div') + Optional(div_id) + ZeroOrMore(div_class)) \
        | div_id | ZeroOrMore(div_class)
    div.setResultsName('div')

    key_value = text + Literal("=") \
        + (Literal("'") + text + Literal("'") | Literal('"') + text + Literal('"'))
    key_value.setResultsName('key_value')

    attribute = Suppress('(') + (key_value | text) + Suppress(')') + Optional(',')

    # TODO: Fix/finish
    tag = (div | Word(alpha)) + ZeroOrMore(attribute)




    include = Suppress(Literal('include')) + char
    include.setResultsName('include')
    include.setParseAction(parse_include)

    line = doctype | include
    source = OneOrMore(line)
    parsed = source.parseString(jade)

    return ' '.join(parsed)

def parse_doctype(results):
    results = " ".join(results)

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

    if results == '!!!' or results == 'doctype':
        return doctypes['transitional']

    left, right = results.split(' ', 1)
    doctype = right.strip().lower()
    return doctypes.get(doctype, doctypes['default'])

def parse_include(results):
    include = ''.join(results) + '.jade'
    if not os.path.isfile(include):
        raise ParseFatalException("File `%s` not found." % include)
    return parse(include)
