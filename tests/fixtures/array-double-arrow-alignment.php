<?php
// @phpcsSniff SymPress.Arrays.ArrayDoubleArrowAlignment

$notAligned = [
    'short' => 'value', // @phpcsErrorOnThisLine AssignmentsNotAligned
    'longer' => 'value',
];

$multipleAssignments = [
    'first' => 'value', 'second' => 'value', // @phpcsErrorOnThisLine AssignmentsNotAligned @phpcsErrorOnThisLine OneAssignmentPerLine
];

$brokenKey = [
    'key'
        => 'value', // @phpcsErrorOnThisLine KeyAndValueNotOnSameLine
];

$valid = [
    'first'  => 'value',
    'second' => 'value',
];
