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
    $node = $nodeFactory->create(FIELD_SIZE, FIELD_SIZE);

    $nodes[$node->getId()] = $node;
}

$networkBuilder = new RandomNetworkBuilder();
//$networkBuilder = new GeneticAlgorithmNetworkBuilder();

$network = $networkBuilder->build($baseStation, $nodes);

$networkExporter = new NetworkExporter();

$oneRoundChargeReducer = new OneRoundChargeReducer();

/**
 * @param int    $n
 * @param string $form1
 * @param string $form2
 * @param string $form3
 *
 * @return string
 */
function pluralForm(int $n, string $form1, string $form2, string $form3): string
{
    if ($n % 10 === 1 && $n % 100 !== 11) {
        return $form1;
    }

    if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
        return $form2;
    }

    return $form3;
}

/**
 * @param int     $rounds
 * @param Network $network
 */
function printStats(int $rounds, Network $network)
{
    echo sprintf(
        '%d %s passed. Dead nodes: %d/%d. Total charge: %s.',
        $rounds,
        pluralForm($rounds, 'round', 'rounds', 'rounds'),
        NODES_COUNT - $network->getNodesCount(),
        NODES_COUNT,
        round($network->getTotalCharge(), 3)
    );

    echo "\n";
}

$rounds         = 0;
$deadNodesCount = $network->getDeadNodesCount();

$networkExporter->export($network, $rounds, true);
printStats($rounds, $network);

while ($network->isAlive()) {
    $oneRoundChargeReducer->reduce($network);

    $network = $networkBuilder->build($baseStation, $nodes);

    $rounds++;
    $newDeadNodesCount = $network->getDeadNodesCount();

    if ($rounds % 100 === 0 || !$network->isAlive() || $deadNodesCount !== $newDeadNodesCount) {
        $networkExporter->export($network, $rounds);
        printStats($rounds, $network);
    }

    $deadNodesCount = $newDeadNodesCount;
}
