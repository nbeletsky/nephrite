import os
import string
from pyparsing import *

grammar = '''
<line> :: <tag> | <comment> | <doctype> | <code> | <include> | <mixin>

<tag>     :: <element> [ <attribute> ] [ <tag-text> ] | <text-only>
<comment> :: <buffered-comment> | <unbuffered-comment> | <block-comment> | <conditional-comment>
<doctype> :: ( "!!!" | "doctype" ) [ "5" | "html" | "xml" | "default" | "transitional" | "strict" | "frameset" | "1.1" | "basic" | "mobile" ]
<code>    :: ( <interpolation> | <php> )
<include> :: "include " <char>
<mixin>   :: <mixin-define> || <mixin-include>

<element>    :: <div> | <alpha>

<div>       :: "div" [ <div_id> ] [ <div_class>+ ]  | <div_id> | <div_class>+
<div_id>    :: "#" <alphanum> [ <extra> ]
<div_class> :: "." <alphanum> [ <extra> ]

<alphanum>   :: <alpha> | <digit>
<alpha>      :: <upper-case> | <lower-case>
<upper-case> :: "A" | ... | "Z"
<lower-case> :: "a" | ... | "z"
<digit>      :: "0" | ... | "9"
<extra>      :: "-" | "_"

<attribute>  :: "(" ( <key-value> | <text> ) [ "," ] ")"
<key-value>  :: <key> "=" ( '"' <text> '"' | "'" <text> "'" )

<key>      :: <alpha> [ <interpolation> ]
<tag-text> :: <text> | ":" <element> <text> | <indent> <text-block> | <indent> <tag>
<text>     :: <char> [ <interpolation> ]

<text-block> :: "| " <text>
<char>       :: <any printable US-ASCII character>

<interpolation>        :: <output-interpolation> | <escape-interpolation>
<output-interpolation> :: "{{" <variable> "}}"
<variable>             :: "$" ( <alphanum> | "_" )
<escape-interpolation> :: "\" <output-interpolation>

<text-only>      :: ( <text-only-tags> [ <attribute> ] | <element> [ <attribute> ] "." ) <indent> <text>
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

    output_interpolation = Combine(Literal('{{') + variable + Literal('}}'))
    escape_interpolation = Combine(Literal('\\') + output_interpolation)
    interpolation = output_interpolation | escape_interpolation
    text = char + ZeroOrMore(interpolation).setResultsName('interpolation')

    text_block = Literal("| ") + text

    tab = Literal('\t') | Literal('  ')
    indent = Literal('\n') + tab

    doctype = oneOf('!!! doctype') + Optional(oneOf('5 html xml default' \
            + 'transitional strict frameset 1.1 basic mobile', True))
    doctype.setParseAction(parse_doctype)

    div_id = Combine(Literal('#') + Word(alphanums + '_' + '-'))
    div_class = Combine(Literal('.') + Word(alphanums + '_' + '-'))

    div = (Literal('div') + Optional(div_id).setResultsName('id') \
        + ZeroOrMore(div_class).setResultsName('class')) \
        | div_id.setResultsName('id') \
        | OneOrMore(div_class).setResultsName('class')

    key = Word(alphas) + ZeroOrMore(interpolation).setResultsName('interpolation')
    key_value = Combine(key + Literal("=") \
        + ((Literal("'") + text + Literal("'")) | (Literal('"') + text + Literal('"'))))

    attribute = Suppress('(') \
        + ZeroOrMore((key_value | text) + Suppress(',')) \
        + (key_value | text) \
        + Suppress(')')

    element = div | Word(alphas)

    tag = Forward()

    tag_text = text | (Literal(':') + element + text) | (indent + text_block) \
        | (indent + tag)

    text_only_tags = oneOf('code script textarea style title', True)
    text_only_tags.setResultsName('text_only_tags')

    text_only = ((text_only_tags + ZeroOrMore(attribute)) \
        | (element + ZeroOrMore(attribute) + Literal('.'))) \
        + indent + text

    tag << ((element.setResultsName('element') \
        + ZeroOrMore(attribute).setResultsName('attribute') \
        + ZeroOrMore(tag_text).setResultsName('tag_text')) \
        | text_only.setResultsName('text_only'))
    tag.setParseAction(parse_tag)

    include = Suppress(Literal('include')) + char
    include.setParseAction(parse_include)

    line = doctype.setResultsName('doctype') | include.setResultsName('include') \
        | tag.setResultsName('tag')
    source = OneOrMore(line).setResultsName('line')
    parsed = source.parseString(jade)
    print(parsed.dump() + '\n')

    return ' '.join(parsed)

def parse_doctype(orig, loc, toks):
    results = ' '.join(toks)

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

def parse_include(orig, loc, toks):
    include = ''.join(toks) + '.jade'
    if not os.path.isfile(include):
        raise ParseFatalException("File `%s` not found." % include)
    return parse(include)

def parse_tag(orig, loc, toks):
    #print(results)
    return toks;
