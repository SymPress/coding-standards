<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

final class SniffMessages
{
    /** @var array<int, list<string>> */
    private array $messages;
    private bool $messagesContainTotal;

    /** @var array<int, list<string>> */
    private readonly array $errors;

    /** @var array<int, list<string>> */
    private readonly array $warnings;

    /**
     * @param array<int, string|list<string>> $warnings
     * @param array<int, string|list<string>> $errors
     * @param array<int, string|list<string>>|null $messages
     */
    public function __construct(
        array $warnings,
        array $errors,
        ?array $messages = null,
    ) {

        $this->warnings = $this->normalizeMessageMap($warnings);
        $this->errors = $this->normalizeMessageMap($errors);
        $this->messages = $messages === null
            ? $this->mergeMessageMaps($this->errors, $this->warnings)
            : $this->normalizeMessageMap($messages);
        $this->messagesContainTotal = $messages === null;
    }

    /** @return array<int, list<string>> */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /** @return list<string> */
    public function messageIn(int $line): array
    {
        return $this->messages[$line] ?? [];
    }

    /** @return array<int> */
    public function getMessageLines(): array
    {
        $messageLines = array_keys($this->messages);

        if ($this->messagesContainTotal) {
            return $messageLines;
        }

        return array_unique(array_merge($this->getErrorLines(), $this->getWarningLines(), $messageLines));
    }

    /** @return array<int, list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return list<string> */
    public function errorIn(int $line): array
    {
        return $this->errors[$line] ?? [];
    }

    /** @return array<int> */
    public function getErrorLines(): array
    {
        return array_keys($this->errors);
    }

    /** @return array<int, list<string>> */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /** @return list<string> */
    public function warningIn(int $line): array
    {
        return $this->warnings[$line] ?? [];
    }

    /** @return array<int> */
    public function getWarningLines(): array
    {
        return array_keys($this->warnings);
    }

    public function getTotal(): int
    {
        if ($this->messagesContainTotal) {
            return $this->countMessages($this->messages);
        }

        return $this->countMessages($this->messages)
            + $this->countMessages($this->errors)
            + $this->countMessages($this->warnings);
    }

    /**
     * @param array<int, string|list<string>> $messages
     * @return array<int, list<string>>
     */
    private function normalizeMessageMap(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $line => $codes) {
            $line = (int) $line;
            $codes = is_array($codes) ? $codes : [$codes];

            foreach ($codes as $code) {
                if (!is_string($code)) {
                    continue;
                }

                $normalized[$line][] = $code;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, list<string>> $left
     * @param array<int, list<string>> $right
     * @return array<int, list<string>>
     */
    private function mergeMessageMaps(array $left, array $right): array
    {
        foreach ($right as $line => $codes) {
            foreach ($codes as $code) {
                $left[$line][] = $code;
            }
        }

        ksort($left);

        return $left;
    }

    /** @param array<int, list<string>> $messages */
    private function countMessages(array $messages): int
    {
        $count = 0;

        foreach ($messages as $codes) {
            $count += count($codes);
        }

        return $count;
    }
}
