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
        '%d %s passed. Dead nodes: %d/%d. Average charge (alive nodes only): %s.',
        $rounds,
        pluralForm($rounds, 'round', 'rounds', 'rounds'),
        $network->getDeadNodesCount(),
        $network->getNodesCount(),
        round($network->getAverageCharge(), 3)
    );

    echo "\n";
}

$rounds         = 0;
$deadNodesCount = $network->getDeadNodesCount();

printStats($rounds, $network);

while ($network->isAlive()) {
    $oneRoundChargeReducer->reduce($network);

    $network = $networkBuilder->build($baseStation, $nodes);

    $rounds++;
    $newDeadNodesCount = $network->getDeadNodesCount();

    if ($rounds % 100 == 0 || $rounds === 1 || !$network->isAlive() || $deadNodesCount !== $newDeadNodesCount) {
        printStats($rounds, $network);
    }

    $deadNodesCount = $newDeadNodesCount;
}
