<?php

namespace App\Services\WhatsApp\AutoReply;

class WhatsAppAutoReplyHumanizer
{
    /**
     * Split reply text into WhatsApp-friendly chunks.
     *
     * @return string[]
     */
    public function splitIntoChunks(string $text, int $maxChars, int $maxChunks): array
    {
        $text = trim(preg_replace("/\r\n?/", "\n", $text) ?? '');
        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split("/\n{2,}/", $text) ?: [$text];
        $chunks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) <= $maxChars) {
                $chunks[] = $paragraph;
            } else {
                $chunks = array_merge($chunks, $this->splitLongParagraph($paragraph, $maxChars));
            }
        }

        if ($chunks === []) {
            $chunks = [mb_substr($text, 0, $maxChars)];
        }

        return array_slice($chunks, 0, max(1, $maxChunks));
    }

    /**
     * @return string[]
     */
    private function splitLongParagraph(string $paragraph, int $maxChars): array
    {
        $parts = [];
        $remaining = $paragraph;

        while (mb_strlen($remaining) > $maxChars) {
            $slice = mb_substr($remaining, 0, $maxChars);
            $breakAt = mb_strrpos($slice, ' ');
            if ($breakAt !== false && $breakAt > (int) ($maxChars * 0.5)) {
                $slice = mb_substr($remaining, 0, $breakAt);
                $remaining = trim(mb_substr($remaining, $breakAt));
            } else {
                $remaining = trim(mb_substr($remaining, $maxChars));
            }
            $parts[] = trim($slice);
        }

        if ($remaining !== '') {
            $parts[] = $remaining;
        }

        return $parts;
    }

    public function randomInitialDelaySeconds(int $min, int $max): int
    {
        $min = max(0, $min);
        $max = max($min, $max);

        return random_int($min, $max);
    }

    public function typingDelayMs(int $typingDurationSeconds): int
    {
        return max(1000, $typingDurationSeconds * 1000);
    }
}
