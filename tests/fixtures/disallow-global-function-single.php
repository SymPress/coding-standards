<?php
// @phpcsSniff SymPress.Functions.DisallowGlobalFunction

class Foo
{
    function test() {

    }
}

// @phpcsErrorOnNextLine
function test() {

}
