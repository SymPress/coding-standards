<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Sniffs\Functions;

use WordPressCS\WordPress\AbstractFunctionRestrictionsSniff;

final class DisallowCallUserFuncSniff extends AbstractFunctionRestrictionsSniff
{
    /** @return array<string, array<string, string|array<string>>> */
    public function getGroups(): array
    {
        return [
            'call_user_func' => [
                'type'      => 'warning',
                'message'   => 'The "%s" function is discouraged; directly call the variable function instead',
                'functions' => [
                    'call_user_func',
                    'call_user_func_array',
                ],
            ],
        ];
    }
}
