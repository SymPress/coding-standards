<?php
// @phpcsSniff SymPress.Complexity.NestingLevel
// @phpcsSniffPropertiesStart
// $warningLimit = 2;
// $errorLimit = 4;
// @phpcsSniffPropertiesEnd

function highNesting(bool $first, bool $second): void // @phpcsWarningOnThisLine High
{
    if ($first) {
        if ($second) {
            echo 'high';
        }
    }
}

function maximumExceeded(bool $first, bool $second, bool $third, bool $fourth): void // @phpcsErrorOnThisLine MaxExceeded
{
    if ($first) {
        if ($second) {
            if ($third) {
                if ($fourth) {
                    echo 'too deep';
                }
            }
        }
    }
}

function acceptable(bool $first): void
{
    if ($first) {
        echo 'fine';
    }
}
