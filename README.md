# Nephrite

Nephrite adds inline PHP scripting support to the [Jade](http://jade-lang.com) template compiler.

## Notes

### Syntax

#### Tags

    html

renders:

    <html></html>

#### Attributes

    div#container
    div#foo.bar.baz
    #foo
    .bar

renders:

    <div id="container"></div>
    <div id="foo" class="bar baz"></div>
    <div id="foo"></div>
    <div class="bar"></div>


#### Text

    p wahoo!
    p
      | foo bar baz
      | rawr rawr
      | super cool
      | go jade go
    a foo
      | bar
      p
        | some crap
      | baz

renders:

    <p>wahoo!</p>
    <p>
      foo bar baz rawr rawr super cool go jade go
    </p>
    <a>
      foo bar
      <p>
        some crap
      </p>
      baz
    </a>

