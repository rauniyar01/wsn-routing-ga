<?php

namespace Podorozhny\Dissertation;

ini_set('memory_limit', -1);

include __DIR__ . '/vendor/autoload.php';

const NODES_COUNT            = 100;
const FIELD_SIZE             = 100;
const ROUND_ITERATIONS_COUNT = 24;

$baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

$nodeFactory = new RandomLocationNodeFactory();

/** @var Node[] $nodes */
$nodes = [];

for ($i = 0; $i < NODES_COUNT; $i++) {
    $nodes[] = $nodeFactory->create(FIELD_SIZE, FIELD_SIZE);
}

$networkBuilder = new RandomNetworkBuilder();
//$networkBuilder = new GeneticAlgorithmNetworkBuilder();

$network = $networkBuilder->build($baseStation, $nodes);

(new NetworkExporter())->export($network);

//$oneRoundChargeReducer = new OneRoundChargeReducer();
//
//$roundsCount = 0;
//
//while ($network->isAlive()) {
//    $oneRoundChargeReducer->reduce($network);
//
//    $network = $networkBuilder->build($baseStation, $nodes);
//
//    var_dump(++$roundsCount);
//}
