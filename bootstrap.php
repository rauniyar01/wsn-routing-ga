<?php

namespace Podorozhny\Dissertation;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

ini_set('memory_limit', -1);

include __DIR__ . '/vendor/autoload.php';

$container = new ContainerBuilder();
$loader    = new YamlFileLoader($container, new FileLocator(__DIR__));
$loader->load('config/services.yml');

/** @var ConsoleOutput $console */
$console = $container->get('console');

/** @var RandomLocationNodeFactory $nodeFactory */
$nodeFactory = $container->get('random_location_node_factory');

/** @var NetworkRunner $networkRunner */
$networkRunner = $container->get('network_runner');

/** @var NetworkExporter $networkExporter */
$networkExporter = $container->get('network_exporter');

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

const BC_SCALE = 16;

const NODES_COUNT            = 100;
const FIELD_SIZE             = 100;
const ROUND_ITERATIONS_COUNT = 24;

$baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

/** @var Node[] $nodes */
$nodes = [];

for ($i = 0; $i < NODES_COUNT; $i++) {
    $node = $nodeFactory->create(FIELD_SIZE, FIELD_SIZE);

    $nodes[] = $node;
}

$console->writeln(
    sprintf(
        '<info>Starting wireless sensor network modelling. Nodes count: %s. Field size: %d x %d meters.</info>',
        number_format(NODES_COUNT, 0, '', ' '),
        number_format(FIELD_SIZE, 0, '', ' '),
        number_format(FIELD_SIZE, 0, '', ' ')
    )
);

$console->writeln(
    sprintf(
        '<info>0 rounds passed. Dead nodes: %d/%d. Total charge: %s.</info>',
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

while ($isAlive = $networkRunner->run($baseStation, $nodes)) {
    $baseStation = null;
    $nodes       = [];

    $network = $networkRunner->getNetwork();
    $rounds  = $networkRunner->getRounds();

    $networkExporter->export($network, $networkRunner->getRounds());

    $console->writeln(
        sprintf(
            '<info>%d %s passed. Dead nodes: %d/%d. Total charge: %s.</info>',
            number_format($rounds, 0, '', ' '),
            pluralForm($rounds, 'round', 'rounds', 'rounds'),
            number_format(NODES_COUNT - $network->getNodesCount(), 0, '', ' '),
            number_format(NODES_COUNT, 0, '', ' '),
            (float) $network->getTotalCharge()
        )
    );
}
