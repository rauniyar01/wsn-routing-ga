<?php

namespace Podorozhny\Dissertation;

use Ramsey\Uuid\Generator\MtRandGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\ConsoleOutput;
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

/** @var ConsoleOutput $output */
$output = $container->get('console_output');

/** @var RandomLocationSensorNodeFactory $nodeFactory */
$nodeFactory = $container->get('random_location_sensor_node_factory');

/** @var NetworkRunner $networkRunner */
$networkRunner = $container->get('network_runner');

/** @var NetworkExporter $networkExporter */
$networkExporter = $container->get('network_exporter');

/** @var SensorNode[] $sensorNodes */
$sensorNodes = [];

for ($i = 0; $i < $container->getParameter('sensor_nodes_count'); $i++) {
    $sensorNode = $nodeFactory->create(
        $container->getParameter('field_size.x'),
        $container->getParameter('field_size.y')
    );

    $sensorNodes[] = $sensorNode;
}

$output->writeln(
    sprintf(
        '<info>Starting wireless sensor network modelling. Sensor nodes count: %s. Field size: %s x %s meters.</info>',
        number_format($container->getParameter('sensor_nodes_count'), 0, '', ' '),
        number_format($container->getParameter('field_size.x'), 0, '', ' '),
        number_format($container->getParameter('field_size.y'), 0, '', ' ')
    )
);

$output->writeln(
    sprintf(
        '<info>0 rounds passed. Dead sensor nodes: %d/%s. Total charge: %s.</info>',
        0,
        number_format($container->getParameter('sensor_nodes_count'), 0, '', ' '),
        (float) array_sum(
            array_map(
                function (SensorNode $sensorNode) {
                    return $sensorNode->getCharge();
                },
                $sensorNodes
            )
        )
    )
);

/** @var BaseStation $baseStation */
$baseStation = $container->get('base_station');

$clearExport = true;

while ($isAlive = $networkRunner->run($baseStation, $sensorNodes)) {
    $baseStation = null;
    $sensorNodes = [];

    $network = $networkRunner->getNetwork();
    $rounds  = $networkRunner->getRounds();

    $networkExporter->export($network, sprintf('round_%d', $rounds), $clearExport);

    $clearExport = false;

    $output->writeln(
        sprintf(
            '<info>%s %s passed. Dead sensor nodes: %s/%s. Total charge: %s.</info>',
            number_format($rounds, 0, '', ' '),
            Util::pluralForm($rounds, 'round', 'rounds', 'rounds'),
            number_format($container->getParameter('sensor_nodes_count') - $network->getSensorNodesCount(), 0, '', ' '),
            number_format($container->getParameter('sensor_nodes_count'), 0, '', ' '),
            (float) $network->getTotalCharge()
        )
    );
}
