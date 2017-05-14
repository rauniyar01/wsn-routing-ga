<?php

namespace Podorozhny\Dissertation;

use Ramsey\Uuid\Generator\MtRandGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

ini_set('memory_limit', -1);

mt_srand((int) (M_PI * 1000000));

const BC_SCALE = 16;

include __DIR__ . '/vendor/autoload.php';
$uuidFactory = new UuidFactory();
$uuidFactory->setRandomGenerator(new MtRandGenerator());
Uuid::setFactory($uuidFactory);

$cacheDirectory = __DIR__ . '/var/cache';

if (!is_dir($cacheDirectory) && (false === @mkdir($cacheDirectory, 0775, true)) || !is_writable($cacheDirectory)
) {
    throw new \Exception(sprintf('Cache directory "%s" is not writable.', $cacheDirectory));
}

$containerCacheFile = $cacheDirectory . '/container.php';

$container = new ContainerBuilder();
$loader    = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load('app/config/services.yml');
$loader->load('app/config/parameters.yml');

$container->compile();

$dumper = new PhpDumper($container);
file_put_contents($containerCacheFile, $dumper->dump(['class' => 'PodorozhnyDissertationServiceContainer']));

/** @var RandomLocationSensorNodeFactory $nodeFactory */
$nodeFactory = $container->get('random_location_sensor_node_factory');

/** @var NetworkExporter $networkExporter */
$networkExporter = $container->get('network_exporter');

/** @var OneRoundChargeReducer $chargeReducer */
$chargeReducer = $container->get('one_round_charge_reducer');

/** @var GeneticAlgorithmNetworkBuilder $networkBuilder */
$networkBuilder = $container->get('network_builder.genetic_algorithm');

/** @var BaseStation $baseStation */
$baseStation = $container->get('base_station');

/** @var SensorNode[] $sensorNodes */
$sensorNodes = [];

$fieldSizeX = $container->getParameter('field_size.x');
$fieldSizeY = $container->getParameter('field_size.y');

for ($i = 0; $i < $container->getParameter('sensor_nodes_count'); $i++) {
    $sensorNode = $nodeFactory->create($fieldSizeX, $fieldSizeY);

    $sensorNodes[] = $sensorNode;
}

/** @var SensorNode[] $clonedSensorNodes */
$clonedSensorNodes = [];

foreach ($sensorNodes as $sensorNode) {
    $clonedSensorNodes[] = clone $sensorNode;
}

$clusterHeads = [];

//$corners = [
//    [0, 0],
//    [$fieldSizeX * 10, 0],
//    [$fieldSizeX * 10, $fieldSizeX * 10],
//    [0, $fieldSizeX * 10],
//];

$corners = [
    [($fieldSizeX * 10) / 4, ($fieldSizeX * 10) / 4],
    [3 * ($fieldSizeX * 10) / 4, ($fieldSizeX * 10) / 4],
    [3 * ($fieldSizeX * 10) / 4, 3 * ($fieldSizeX * 10) / 4],
    [($fieldSizeX * 10) / 4, 3 * ($fieldSizeX * 10) / 4],
];

$distances = [];

foreach ($sensorNodes as $key => $sensorNode) {
    foreach ($corners as $k => $corner) {
        $distance = $sensorNode->distanceTo($corner[0], $corner[1]);

        if (!array_key_exists($k, $distances) || $distance < $distances[$k]['d']) {
            $distances[$k] = ['d' => $distance, 'k' => $key];
        }
    }
}

$clusterHeads[] = $sensorNodes[$distances[0]['k']];
$clusterHeads[] = $sensorNodes[$distances[1]['k']];
$clusterHeads[] = $sensorNodes[$distances[2]['k']];
$clusterHeads[] = $sensorNodes[$distances[3]['k']];

unset($sensorNodes[$distances[0]['k']]);
unset($sensorNodes[$distances[1]['k']]);
unset($sensorNodes[$distances[2]['k']]);
unset($sensorNodes[$distances[3]['k']]);

foreach ($clusterHeads as $sensorNode) {
    $sensorNode->makeClusterHead();
}

foreach ($sensorNodes as $sensorNode) {
    $nearestClusterHead = $sensorNode->getNearestNeighbor($clusterHeads);

    if (!$nearestClusterHead instanceof Node ||
        $sensorNode->distanceToNeighbor($baseStation) <= $sensorNode->distanceToNeighbor($nearestClusterHead)
    ) {
        $nearestClusterHead = $baseStation;
    }

    $sensorNode->makeClusterNode($nearestClusterHead);
}

$network = new Network($baseStation, $clusterHeads, $sensorNodes);

$networkExporter->export($network, 'handmade', true);

$chargeReducer->reduce($network);

$geneticNetwork = $networkBuilder->build($baseStation, $clonedSensorNodes);

$networkExporter->export($geneticNetwork, 'genetic', false);

$chargeReducer->reduce($geneticNetwork);

var_dump($network->getTotalCharge());
var_dump($geneticNetwork->getTotalCharge());
