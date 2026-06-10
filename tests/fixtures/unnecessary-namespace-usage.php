<?php
// @phpcsSniff SymPress.Formatting.UnnecessaryNamespaceUsage

namespace SymPressCS\Fixtures;

use Vendor\Service;

final class UnnecessaryNamespaceUsageFixture
{
    /** @param \Vendor\Service $service */ // @phpcsWarningOnThisLine UnnecessaryNamespaceUsage
    public function __construct(\Vendor\Service $service) // @phpcsWarningOnThisLine UnnecessaryNamespaceUsage
    {
    }

    public function create(): \SymPressCS\Fixtures\LocalValue // @phpcsWarningOnThisLine UnnecessaryNamespaceUsage
    {
        return new \SymPressCS\Fixtures\LocalValue(); // @phpcsWarningOnThisLine UnnecessaryNamespaceUsage
    }
}

final class LocalValue
{
}
