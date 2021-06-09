<?php

class FuzzySearch
{
    public string $encoding = 'utf8';
    public bool $fuzzyNumbers = false;
    public bool $partialSearch = false;
    private array $wordList = [];
    private array $variants = [];
    private int $maxErrorCount = 0;
    private int $errors = 0;

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
     * @param string $word
     * @return bool
     */
    private function checkNumber(string $word): bool
    {
        if ($this->fuzzyNumbers) {
            return false;
        }

        return preg_replace('/[0-9]+/u', '', $word) === '';
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

        if ($this->maxErrorCount >= $searchLength) {
            return true;
        }

        $i = 0;
        do {
            $firstSymbol = $searchWord[$i];
            $firstSymbolPos = $i;
            $i++;
        } while (!$this->checkNumber($firstSymbol) && !in_array($firstSymbol, $word, true) && $i < $this->maxErrorCount);

        foreach ($word as $pos => $symbol) {
            if ($symbol === $firstSymbol) {
                $this->errors = $firstSymbolPos;

                foreach ($searchWord as $searchPos => $searchSymbol) {
                    if ($pos + $searchPos - $firstSymbolPos > $length - 1) {
                        break;
                    }

                    if (!$this->partialSearch && $pos + $searchPos - $firstSymbolPos > $searchLength - 1) {
                        break;
                    }

                    if ($searchPos < $firstSymbolPos) {
                        continue;
                    }

                    if ($word[$pos + $searchPos - $firstSymbolPos] !== $searchSymbol) {
                        if ($this->checkNumber($firstSymbol)) {
                            break;
                        }

                        $this->errors++;
                    }

                    if ($searchPos === $searchLength - 1 && $this->errors <= $this->maxErrorCount) {
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
     * @param string $word
     */
    private function getWordVariants(string $word): void
    {
        $this->variants[] = $word;
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
    public function search(string $searchWord, int $maxErrorCount = 0): array
    {
        $this->maxErrorCount = $maxErrorCount;
        for ($i = 0; $i <= $maxErrorCount; $i++) {
            $words[$i] = [];
        }

        $this->getWordVariants($searchWord);

        foreach ($this->variants as $variant) {
            $searchWordArray = $this->str2arr($variant);
            foreach ($this->wordList as $word) {
                $wordArray = $this->str2arr($word);

                if ($this->compare($wordArray, $searchWordArray)) {
                    $words[$this->errors][] = $word;
                }
            }
        }

        $result = [];
        foreach ($words as $v) {
            $result = array_merge($result, $v);
        }

        return array_unique($result);
    }
}