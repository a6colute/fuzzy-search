<?php

class FuzzySearch
{
    private array $wordList = [];

    /**
     * @param string $word
     * @return string[]
     */
    private function getWordVariants(string $word, int $maxErrorCount): array
    {

    }

    /**
     * @param string[] $wordList
     */
    public function loadWords(array $wordList): void
    {
        $this->wordList = $wordList;
    }

    /**
     * @param string $word
     * @param int $maxErrorCount
     * @return array
     */
    public function search(string $word, int $maxErrorCount): array
    {
        $variants = $this->getWordVariants($word, $maxErrorCount);
        foreach ($variants as $variant) {

        }
    }
}