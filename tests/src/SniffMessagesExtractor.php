<?php

declare(strict_types=1);

namespace SymPressCS\SymPress\Tests;

use PHP_CodeSniffer\Files\File;

class SniffMessagesExtractor
{
    public function __construct(private readonly File $file)
    {
    }

    public function extractMessages(): SniffMessages
    {
        $this->file->process();

        return new SniffMessages(
            $this->normalize($this->file->getWarnings()),
            $this->normalize($this->file->getErrors()),
        );
    }

    /**
     * @param array<array-key, mixed> $fileMessages
     * @return array<int, list<string>>
     */
    private function normalize(array $fileMessages): array
    {
        $normalized = [];

        /** @var array<array<array<string>>> $lineMessages */
        foreach ($fileMessages as $line => $lineMessages) {
            foreach ($this->normalizeLineMessages((int) $line, $lineMessages) as $normalizedLine => $codes) {
                foreach ($codes as $code) {
                    $normalized[$normalizedLine][] = $code;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param int $line
     * @param array<array<array<string>>> $lineMessages
     * @return array<int, list<string>>
     */
    private function normalizeLineMessages(int $line, array $lineMessages): array
    {
        $normalized = [];

        foreach ($lineMessages as $messages) {
            $message = array_shift($messages);
            $sourceParts = explode('.', ($message['source'] ?? ''));
            $normalized[$line][] = (string) end($sourceParts);
        }

        return $normalized;
    }
}
