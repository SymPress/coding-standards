<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Classes;

use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Scopes;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class DeprecatedSerializeMagicMethodSniff implements Sniff
{
    /** @var array<string, string>  */
    public array $deprecatedMethods = [
        '__sleep'  => '__serialize',
        '__wakeup' => '__unserialize',
    ];

    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!Scopes::isOOMethod($phpcsFile, $stackPtr)) {
            return;
        }

        $name = FunctionDeclarations::getName($phpcsFile, $stackPtr);

        if ($name === null) {
            return;
        }

        $alternative = $this->deprecatedMethods[$name] ?? null;

        if ($alternative === null) {
            return;
        }

        $phpcsFile->addError(
            'The "%s" method has been deprecated; use "%s" instead',
            $stackPtr,
            'Found',
            [$name, $alternative],
        );
    }
}
