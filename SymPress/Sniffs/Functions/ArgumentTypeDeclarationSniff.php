<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Functions;

use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Scopes;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SymPressCS\SymPress\Helpers\FunctionDocBlock;
use SymPressCS\SymPress\Helpers\Functions;
use SymPressCS\SymPress\Helpers\Hooks;

final class ArgumentTypeDeclarationSniff implements Sniff
{
    /** @var list<string> */
    public array $allowedMethodNames = [];

    /** @var list<string> */
    public array $defaultAllowedMethodNames = [
        'seek',
        'unserialize',
    ];

    /** @return list<int|string> */
    public function register(): array
    {
        return [
            T_CLOSURE,
            T_FN,
            T_FUNCTION,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $phpcsFile->getTokens();

        if ($this->shouldIgnore($phpcsFile, (int) $stackPtr, $tokens)) {
            return;
        }

        /** @var array<array{name: string, type_hint?: string|false}> $parameters */
        $parameters = FunctionDeclarations::getParameters($phpcsFile, $stackPtr);

        $docBlockTypes = FunctionDocBlock::allParamTypes($phpcsFile, $stackPtr);

        $errors = [];
        foreach ($parameters as $parameter) {
            $typeHint = $parameter['type_hint'] ?? '';
            if (($typeHint !== '') && ($typeHint !== false)) {
                continue;
            }

            $docTypes = $docBlockTypes[$parameter['name']] ?? [];
            if (Functions::isNonDeclarableDocBlockType($docTypes, false)) {
                continue;
            }

            $errors[] = $parameter['name'];
        }

        if (!$errors) {
            return;
        }

        $phpcsFile->addWarning(
            'Argument type is missing (parameters: %s)',
            $stackPtr,
            'NoArgumentType',
            [implode(', ', $errors)],
        );
    }

    private function isAllowedMethodName(string $name): bool
    {
        static $allowedMethodNames;
        if (!is_array($allowedMethodNames)) {
            $allowedMethodNames = array_unique(array_merge(
                $this->defaultAllowedMethodNames,
                $this->allowedMethodNames,
            ));
        }

        return in_array($name, $allowedMethodNames, true);
    }

    /**
     * @param File $file
     * @param int $position
     * @param array<int, array<string, mixed>> $tokens
     */
    private function shouldIgnore(File $file, int $position, array $tokens): bool
    {
        if (
            Functions::isArrayAccess($file, $position)
            || Functions::isPsrMethod($file, $position)
            || FunctionDeclarations::isSpecialMethod($file, $position)
            || Hooks::isHookClosure($file, $position)
            || Hooks::isHookFunction($file, $position)
        ) {
            return true;
        }

        if (!Scopes::isOOMethod($file, $position)) {
            return false;
        }

        return $this->isAllowedMethodName(
            ($tokens[$position]['code'] ?? '') !== T_FN
                ? (string) FunctionDeclarations::getName($file, $position)
                : '',
        );
    }
}
