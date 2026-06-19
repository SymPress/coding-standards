<?php
// @phpcsSniff SymPress.Classes.ClassLength
// @phpcsSniffPropertiesStart
// $maxLength = 5;
// @phpcsSniffPropertiesEnd

class CompactClass {
    public function ok(): void
    {
    }
}

// @phpcsWarningOnNextLine TooLong
class LongClass {
    public function tooLong(): void
    {
        $first = 1;
        $second = 2;
        $third = 3;
        $fourth = 4;
    }
}
