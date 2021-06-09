<?php

class FuzzySearch
{
    public string $encoding = 'utf8';
    private array $wordList = [];
    private array $variants = [];
    private string $specialSymbol = '*';

    /**
     * @param string $word
     * @return array
     */
    private function str2arr(string $word): array
    {
        $wordArray = [];

        $length = mb_strlen($word, $this->encoding);
        for ($i = 0; $i < $length; $i++) {
            $wordArray[] = mb_substr($word, $i, 1);
        }

        return $wordArray;
    }

    /**
     * @param array $word
     * @param array $searchWord
     * @return bool
     */
    private function compare(array $word, array $searchWord): bool
    {
        $length = count($word);
        $searchLength = count($searchWord);
        $success = false;

        $i = 0;
        do {
            $firstSymbol = $searchWord[$i];
            $firstSymbolPos = $i;
            $i++;
        } while ($firstSymbol === $this->specialSymbol && $i < $searchLength);

        foreach ($word as $pos => $symbol) {
            if ($symbol === $firstSymbol) {
                foreach ($searchWord as $searchPos => $searchSymbol) {
                    if ($searchPos < $firstSymbolPos) {
                        continue;
                    }

                    if ($searchSymbol !== $this->specialSymbol && ($pos + $searchPos - $firstSymbolPos > $length - 1 || $word[$pos + $searchPos - $firstSymbolPos] !== $searchSymbol)) {
                        break;
                    }

                    if ($searchPos === $searchLength - 1) {
                        $success = true;
                        break;
                    }
                }
            }

            if ($success) {
                break;
            }
        }

        return $success;
    }

    /**
     * @param array $word
     * @param int $maxErrorCount
     * @param int $depth
     * @param int $position
     */
    private function createVariant(array $word, int $maxErrorCount, int $depth = 0, int $position = 0): void
    {
        $length = count($word);
        $depth++;
        for ($i = $position; $i < $length; $i++) {
            $newWord = $word;
            $newWord[$i] = $this->specialSymbol;

            if ($depth === $maxErrorCount) {
                $this->variants[] = implode('', $newWord);
            } elseif ($i + 1 < $length) {
                $this->createVariant($newWord, $maxErrorCount, $depth, $i + 1);
            }
        }
    }

    /**
     * @param string $word
     * @param int $maxErrorCount
     */
    private function getWordVariants(string $word, int $maxErrorCount): void
    {
        $this->variants[] = $word;
        $wordArray = $this->str2arr($word);
        for ($errorCount = 1; $errorCount <= $maxErrorCount; $errorCount++) {
            $this->createVariant($wordArray, $errorCount);
        }
    }

    /**
     * @param string[] $wordList
     */
    public function loadWords(array $wordList): void
    {
        $this->wordList = $wordList;
    }

    /**
     * @param string $searchWord
     * @param int $maxErrorCount
     * @return array
     */
    public function search(string $searchWord, int $maxErrorCount): array
    {
        $result = [];

        $this->getWordVariants($searchWord, $maxErrorCount);

        foreach ($this->variants as $variant) {
            $searchWordArray = $this->str2arr($variant);
            foreach ($this->wordList as $word) {
                $wordArray = $this->str2arr($word);

                if ($this->compare($wordArray, $searchWordArray)) {
                    $result[] = $word;
                }
            }
        }

        return array_unique($result);
    }
}