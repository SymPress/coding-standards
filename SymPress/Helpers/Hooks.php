<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

final class Hooks
{
    public static function isHookClosure(
        File $file,
        int $position,
        bool $lookForFilters = true,
        bool $lookForActions = true,
    ): bool {
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $file->getTokens();

        $code = $tokens[$position]['code'] ?? null;

        if (!in_array($code, [T_CLOSURE, T_FN], true)) {
            return false;
        }

        $empty = Tokens::$emptyTokens;

        $exclude  = array_merge($empty, [T_STATIC]);
        $commaPos = $file->findPrevious($exclude, $position - 1, null, true, null, true);

        if ($commaPos === false) {
            return false;
        }

        $code = $tokens[$commaPos]['code'] ?? null;

        if ($code !== T_COMMA) {
            return false;
        }

        $openType    = T_OPEN_PARENTHESIS;
        $openCallPos = $file->findPrevious($openType, $commaPos - 2, null, false, null, true);

        if ($openCallPos === false) {
            return false;
        }

        $functionCallPos = $file->findPrevious($empty, $openCallPos - 1, null, true, null, true);

        $code = $tokens[$functionCallPos]['code'] ?? null;

        if ($code !== T_STRING) {
            return false;
        }

        return array_key_exists(
            (string) ($tokens[$functionCallPos]['content'] ?? ''),
            array_filter([
                'add_action' => $lookForActions,
                'add_filter' => $lookForFilters,
            ]),
        );
    }

    public static function isHookFunction(File $file, int $position): bool
    {
        return (bool) FunctionDocBlock::tag('wp-hook', $file, $position);
    }
}
