<?php
// @phpcsSniff SymPress.Strings.VariableInDoubleQuotes

$name = 'SymPress';
$message = "Hello $name"; // @phpcsErrorOnThisLine NotSurroundedWithBraces
$valid = "Hello {$name}";
