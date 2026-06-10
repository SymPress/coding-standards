<?php
// @phpcsSniff SymPress.Usage.IsNull

if (is_null($value)) { // @phpcsWarningOnThisLine IsNull
    echo 'null';
}

if (!is_null($value)) { // @phpcsWarningOnThisLine IsNull
    echo 'not null';
}

if ($value === is_null($other)) { // @phpcsWarningOnThisLine IsNull
    echo 'comparison result';
}
