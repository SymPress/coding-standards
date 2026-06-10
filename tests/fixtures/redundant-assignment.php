<?php
// @phpcsSniff SymPress.Variables.RedundantAssignment

final class RedundantAssignmentFixture
{
    public function decodeMessages(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return (array) $decoded; // @phpcsWarningOnThisLine Found
    }

    public function decodeMessagesDirectly(string $json): array
    {
        return (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function keepVariableWhenMoreWorkHappens(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    public function reportValueAssignedBeforeBusinessCode(): string
    {
        $foo = 'bar';

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        return sprintf('%s: %s', $prefix, $foo); // @phpcsWarningOnThisLine Found
    }

    public function reportLiteralAssignedBeforeBusinessCode(): string
    {
        $foo = 'bar';

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        return sprintf('%s', $foo); // @phpcsWarningOnThisLine Found
    }

    public function reportMethodCallAssignedBeforeBusinessCode(): string
    {
        $foo = $this->buildValue();

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        return sprintf('%s', $foo); // @phpcsWarningOnThisLine Found
    }

    public function reportSingleUseVariableInMultiArgumentReturn(
        array $tokens,
        int $commentOpener,
        int $closeTag,
        bool $normalizeContent,
    ): array {

        $tags = $this->findAllTags($tokens, $commentOpener + 1, $closeTag);

        return $this->normalizeTags($tags, $normalizeContent); // @phpcsWarningOnThisLine Found
    }

    public function throwImmediately(): never
    {
        $exception = new RuntimeException('Nope');

        throw $exception; // @phpcsWarningOnThisLine Found
    }

    public function printLiteralAfterBusinessCode(): void
    {
        $message = 'Hello';

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        print $message; // @phpcsWarningOnThisLine Found
    }

    public function echoLiteralAfterBusinessCode(): void
    {
        $message = 'Hello';

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        echo $message; // @phpcsWarningOnThisLine Found
    }

    public function yieldLiteralAfterBusinessCode(): Generator
    {
        $value = 'Hello';

        $prefix = 'Result';
        $prefix = strtoupper($prefix);

        yield $value; // @phpcsWarningOnThisLine Found
    }

    public function keepVariableUsedTwice(): string
    {
        $foo = 'bar';

        return sprintf('%s %s', $foo, $foo);
    }

    public function keepClosureParameter(): Closure
    {
        $foo = 'bar';

        return static function (string $foo): string {
            return $foo;
        };
    }

    public function keepClosureCapture(): Closure
    {
        $foo = 'bar';

        return static fn(): string => $foo;
    }

    private function buildValue(): string
    {
        return 'bar';
    }
}
