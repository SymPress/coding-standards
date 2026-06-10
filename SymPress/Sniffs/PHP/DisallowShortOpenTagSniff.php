<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\PHP;

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\DisallowShortOpenTagSniff as BaseSniff;

final class DisallowShortOpenTagSniff extends BaseSniff
{
    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_INLINE_HTML,
            T_OPEN_TAG,
        ];
    }
}
