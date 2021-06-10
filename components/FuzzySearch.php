<?php

class FuzzySearch
{
    public string $encoding = 'utf8';
    public bool $fuzzyNumbers = false;
    public bool $partialSearch = false;
    private array $wordList = [];
    private array $variants = [];
    private int $maxErrorCount = 0;
    public float $errorWeight = 2;
    public float $wordLengthWeight = 2;
    public float $wordPositionWeight = 2;
    public float $minRelevancy = 0.1;

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
     * @param int $wordLength
     * @param int $errorCount
     * @return float
     */
    private function calculateErrorWeight(int $wordLength, int $errorCount): float
    {
        return abs(($wordLength - $errorCount)/$wordLength) ** $this->errorWeight;
    }

    /**
     * @param int $wordLength
     * @param int $searchWordLength
     * @return float
     */
    private function calculateLengthDifferenceWeight(int $wordLength, int $searchWordLength): float
    {
        return abs(($searchWordLength - abs($wordLength - $searchWordLength))/$searchWordLength) ** $this->wordLengthWeight;
    }

    private function calculatePositionWeight(int $position, int $wordLength, int $searchWordLength): float
    {
        $positionDifference = $position + abs($wordLength - $searchWordLength - $position);
        return abs(($searchWordLength - $positionDifference)/$searchWordLength) ** $this->wordPositionWeight;
    }

    /**
     * @param array $word
     * @param array $searchWord
     * @return float
     */
    private function compare(array $word, array $searchWord): float
    {
        $length = count($word);
        $searchLength = count($searchWord);
        $relevancy = 0;

        $i = 0;
        do {
            $firstSymbol = $searchWord[$i];
            $firstSymbolPos = $i;
            $i++;
        } while (!$this->checkNumber($firstSymbol) && !in_array($firstSymbol, $word, true) && $i < $this->maxErrorCount);

        foreach ($word as $pos => $symbol) {
            if ($symbol === $firstSymbol) {
                $errors = $firstSymbolPos;

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

                        $errors++;
                    }

                    if ($searchPos === $searchLength - 1 && $errors <= $this->maxErrorCount) {
                        $relevancy =
                            $this->calculateErrorWeight($searchLength, $errors) *
                            $this->calculateLengthDifferenceWeight($length, $searchLength) *
                            $this->calculatePositionWeight($pos, $length, $searchLength)
                        ;
                        break;
                    }
                }
            }

            if ($relevancy !== 0) {
                break;
            }
        }

        return $relevancy;
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
        $words = [];

        $this->getWordVariants($searchWord);

        foreach ($this->variants as $variant) {
            $searchWordArray = $this->str2arr($variant);
            foreach ($this->wordList as $word) {
                $wordArray = $this->str2arr($word);

                if (
                    ($relevancy = $this->compare($wordArray, $searchWordArray)) &&
                    $this->minRelevancy <= $relevancy
                ) {
                    $words[$word] = $relevancy;
                }
            }
        }

        //sort($words);

        return array_unique($words);
    }
}