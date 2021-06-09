<?php

require 'components/FuzzySearch.php';

$search = new FuzzySearch();
$search->loadWords(['кольцо', 'колье', 'кальций', 'коса', 'мать']);
$result = $search->search('каль', 2);
var_dump($result);