<?php

namespace Podorozhny\Dissertation;

use Ramsey\Uuid\Generator\MtRandGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

ini_set('memory_limit', -1);

mt_srand((int) (M_PI * 1000000));

include __DIR__ . '/vendor/autoload.php';

$uuidFactory = new UuidFactory();
$uuidFactory->setRandomGenerator(new MtRandGenerator());
Uuid::setFactory($uuidFactory);

$container = new ContainerBuilder();
$loader    = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load('config/services.yml');

/** @var RandomLocationNodeFactory $nodeFactory */
$nodeFactory = $container->get('random_location_node_factory');

/** @var NetworkExporter $networkExporter */
$networkExporter = $container->get('network_exporter');

/** @var OneRoundChargeReducer $chargeReducer */
$chargeReducer = $container->get('one_round_charge_reducer');

/** @var GeneticAlgorithmNetworkBuilder $networkBuilder */
$networkBuilder = $container->get('network_builder.genetic_algorithm');

const BC_SCALE = 16;

const NODES_COUNT            = 100;
const FIELD_SIZE_X           = 100;
const FIELD_SIZE_Y           = 100;
const ROUND_ITERATIONS_COUNT = 24;

$baseStation = new BaseStation(FIELD_SIZE_X * 10 / 2, FIELD_SIZE_Y * 10 / 2);

/** @var Node[] $nodes */
$nodes = [];

for ($i = 0; $i < NODES_COUNT; $i++) {
    $node = $nodeFactory->create(FIELD_SIZE_X, FIELD_SIZE_Y);

    $nodes[] = $node;
}

/** @var Node[] $clonedNodes */
$clonedNodes = [];

foreach ($nodes as $node) {
    $clonedNodes[] = clone $node;
}

$clusterHeads = [];

//$corners = [
//    [0, 0],
//    [FIELD_SIZE_X * 10, 0],
//    [FIELD_SIZE_X * 10, FIELD_SIZE_X * 10],
//    [0, FIELD_SIZE_X * 10],
//];

$corners = [
    [(FIELD_SIZE_X * 10) / 4, (FIELD_SIZE_Y * 10) / 4],
    [3 * (FIELD_SIZE_X * 10) / 4, (FIELD_SIZE_Y * 10) / 4],
    [3 * (FIELD_SIZE_X * 10) / 4, 3 * (FIELD_SIZE_Y * 10) / 4],
    [(FIELD_SIZE_X * 10) / 4, 3 * (FIELD_SIZE_Y * 10) / 4],
];

$distances = [];

foreach ($nodes as $key => $node) {
    foreach ($corners as $k => $corner) {
        $distance = $node->distanceTo($corner[0], $corner[1]);

        if (!array_key_exists($k, $distances) || $distance < $distances[$k]['d']) {
            $distances[$k] = ['d' => $distance, 'k' => $key];
        }
    }
}

$clusterHeads[] = $nodes[$distances[0]['k']];
$clusterHeads[] = $nodes[$distances[1]['k']];
$clusterHeads[] = $nodes[$distances[2]['k']];
$clusterHeads[] = $nodes[$distances[3]['k']];

unset($nodes[$distances[0]['k']]);
unset($nodes[$distances[1]['k']]);
unset($nodes[$distances[2]['k']]);
unset($nodes[$distances[3]['k']]);

foreach ($clusterHeads as $node) {
    $node->makeClusterHead();
}

foreach ($nodes as $node) {
    $nearestClusterHead = $node->getNearestNeighbor($clusterHeads);

    if (!$nearestClusterHead instanceof Node ||
        $node->distanceToNeighbor($baseStation) <= $node->distanceToNeighbor($nearestClusterHead)
    ) {
        $nearestClusterHead = $baseStation;
    }

    $node->makeClusterNode($nearestClusterHead);
}

$network = new Network($baseStation, $clusterHeads, $nodes);

$networkExporter->export($network, 1, true);

$chargeReducer->reduce($network);

//$geneticNetwork = $networkBuilder->build($baseStation, $clonedNodes);
//
//$networkExporter->export($geneticNetwork, 2, false);
//
//$chargeReducer->reduce($geneticNetwork);

var_dump($network->getTotalCharge());
//var_dump($geneticNetwork->getTotalCharge());
