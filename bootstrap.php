<?php

namespace Podorozhny\Dissertation;

use Ramsey\Uuid\Generator\MtRandGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

ini_set('memory_limit', -1);

mt_srand((int) (M_PI * 1000000));

const BC_SCALE = 16;

const NODES_COUNT            = 100;
const FIELD_SIZE_X           = 100;
const FIELD_SIZE_Y           = 100;
const ROUND_ITERATIONS_COUNT = 24;

include __DIR__ . '/vendor/autoload.php';
$uuidFactory = new UuidFactory();
$uuidFactory->setRandomGenerator(new MtRandGenerator());
Uuid::setFactory($uuidFactory);

$container = new ContainerBuilder();
$loader    = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load('config/services.yml');

$container->getDefinition('base_station')
    ->setArguments([FIELD_SIZE_X * 10 / 2, FIELD_SIZE_Y * 10 / 2]);

/** @var ConsoleOutput $console */
$console = $container->get('console');

/** @var RandomLocationNodeFactory $nodeFactory */
$nodeFactory = $container->get('random_location_node_factory');

/** @var NetworkRunner $networkRunner */
$networkRunner = $container->get('network_runner');

/** @var NetworkExporter $networkExporter */
$networkExporter = $container->get('network_exporter');

/** @var Node[] $nodes */
$nodes = [];

for ($i = 0; $i < NODES_COUNT; $i++) {
    $node = $nodeFactory->create(FIELD_SIZE_X, FIELD_SIZE_Y);

    $nodes[] = $node;
}

$console->writeln(
    sprintf(
        '<info>Starting wireless sensor network modelling. Nodes count: %s. Field size: %s x %s meters.</info>',
        number_format(NODES_COUNT, 0, '', ' '),
        number_format(FIELD_SIZE_X, 0, '', ' '),
        number_format(FIELD_SIZE_Y, 0, '', ' ')
    )
);

$console->writeln(
    sprintf(
        '<info>0 rounds passed. Dead nodes: %d/%s. Total charge: %s.</info>',
        0,
        number_format(NODES_COUNT, 0, '', ' '),
        (float) array_sum(
            array_map(
                function (Node $node) {
                    return $node->getCharge();
                },
                $nodes
            )
        )
    )
);

/** @var BaseStation $baseStation */
$baseStation = $container->get('base_station');

$firstExport = true;

while ($isAlive = $networkRunner->run($baseStation, $nodes)) {
    $baseStation = null;
    $nodes       = [];

    $network = $networkRunner->getNetwork();
    $rounds  = $networkRunner->getRounds();

    $networkExporter->export($network, $networkRunner->getRounds(), !$firstExport);

    $firstExport = false;

    $console->writeln(
        sprintf(
            '<info>%s %s passed. Dead nodes: %s/%s. Total charge: %s.</info>',
            number_format($rounds, 0, '', ' '),
            Util::pluralForm($rounds, 'round', 'rounds', 'rounds'),
            number_format(NODES_COUNT - $network->getNodesCount(), 0, '', ' '),
            number_format(NODES_COUNT, 0, '', ' '),
            (float) $network->getTotalCharge()
        )
    );

    die();
}
