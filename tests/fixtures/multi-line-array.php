<?php
// @phpcsSniff SymPress.Arrays.MultiLineArray

$shortArray = ['first', // @phpcsErrorOnThisLine OpeningMustBeFollowedByNewline
    'second']; // @phpcsErrorOnThisLine ClosingMustBeInOwnLine

$longArray = array('first', // @phpcsErrorOnThisLine OpeningMustBeFollowedByNewline
    'second'); // @phpcsErrorOnThisLine ClosingMustBeInOwnLine

$valid = [
    'first',
    'second',
];
