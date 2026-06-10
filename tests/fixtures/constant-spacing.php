<?php
// @phpcsSniff SymPress.WhiteSpace.ConstantSpacing

final class ConstantSpacingFixture
{
    public const  VALUE = 'bad'; // @phpcsErrorOnThisLine Incorrect
    public const VALID = 'good';
}
