<?php
// @phpcsSniff SymPress.Strings.VariableInDoubleQuotes

$name = 'SymPress';
$message = "Hello $name"; // @phpcsErrorOnThisLine NotSurroundedWithBraces
$valid = "Hello {$name}";
$items = ['01' => 'first'];
$ambiguous = "Item $items[01]"; // @phpcsErrorOnThisLine NotSurroundedWithBraces
$escaped = "\$name is not interpolated";
