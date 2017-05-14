<?php

namespace Podorozhny\Dissertation;

use Symfony\Component\Finder\Finder;

/** @todo use symfony console component maybe */
final class NetworkExporter
{
    /** @var int */
    private $fieldSizeX;

    /** @var int */
    private $fieldSizeY;

    /** @var string */
    private $content;

    public function __construct(int $fieldSizeX, int $fieldSizeY)
    {
        $this->fieldSizeX = $fieldSizeX;
        $this->fieldSizeY = $fieldSizeY;
    }

    /**
     * @param Network $network
     * @param int     $rounds
     * @param bool    $clear
     *
     * @throws \Exception
     */
    public function export(Network $network, int $rounds, bool $clear = false)
    {
        $fileName = $this->getFilePath($rounds);

        $directory = dirname($fileName);

        if ($clear) {
            /** @var \SplFileInfo $file */
            foreach ((new Finder())->files()->in($directory) as $file) {
                unlink($file->getRealPath());
            }
        }

        if (!is_dir($directory) && (false === @mkdir($directory, 0775, true)) || !is_writable($directory)
        ) {
            throw new \Exception(sprintf('Export directory "%s" is not writable.', $directory));
        }

        $tmpFileName = $fileName . '.' . uniqid('', true);

        file_put_contents($tmpFileName, $this->getFileContent($network));

        @chmod($tmpFileName, 0664);
        rename($tmpFileName, $fileName);
    }

    /**
     * @param Network $network
     *
     * @return string
     */
    private function getFileContent(Network $network): string
    {
        $this->content = '';

        $plotIndex = 0;

        $this->addExpression('clear;');
        $this->addExpression('close all;');
        $this->addExpression('figure;');
        $this->addExpression('hold on;');

        $baseStation = $network->getBaseStation();

        $this->addNewLine();
        $this->addComment('Connections between cluster heads and base station');

        foreach ($network->getClusterHeads() as $sensorNode) {
            $this->addExpression(
                sprintf(
                    "plot%d = plot([%s %s], [%s %s], '--r');",
                    ++$plotIndex,
                    $this->convertCoordinate($sensorNode->getX()),
                    $this->convertCoordinate($baseStation->getX()),
                    $this->convertCoordinate($sensorNode->getY()),
                    $this->convertCoordinate($baseStation->getY())
                )
            );

            $this->addExpression(sprintf('plot%d.Color(4) = 0.25;', $plotIndex));
        }

        $this->addNewLine();
        $this->addComment('Connections between cluster nodes and heads');

        foreach ($network->getClusterNodes() as $sensorNode) {
            $this->addExpression(
                sprintf(
                    "plot%d = plot([%s %s], [%s %s], '--k');",
                    ++$plotIndex,
                    $this->convertCoordinate($sensorNode->getX()),
                    $this->convertCoordinate($sensorNode->getClusterHead()->getX()),
                    $this->convertCoordinate($sensorNode->getY()),
                    $this->convertCoordinate($sensorNode->getClusterHead()->getY())
                )
            );

            $this->addExpression(sprintf('plot%d.Color(4) = 0.25;', $plotIndex));
        }

        $this->addNewLine();
        $this->addComment('Base station');

        $this->addExpression(
            sprintf(
                "plot%d = plot(%s, %s, 'd', 'MarkerSize', 12, 'MarkerEdgeColor', 'k', 'MarkerFaceColor', 'r');",
                ++$plotIndex,
                $this->convertCoordinate($baseStation->getX()),
                $this->convertCoordinate($baseStation->getY())
            )
        );

        $this->addNewLine();
        $this->addComment('Cluster heads');

        foreach ($network->getClusterHeads() as $sensorNode) {
            $this->addExpression(
                sprintf(
                    "plot%d = plot(%s, %s, 's', 'MarkerSize', 8, 'MarkerEdgeColor', 'k', 'MarkerFaceColor', 'r');",
                    ++$plotIndex,
                    $this->convertCoordinate($sensorNode->getX()),
                    $this->convertCoordinate($sensorNode->getY())
                )
            );
        }

        $this->addNewLine();
        $this->addComment('Cluster nodes');

        foreach ($network->getClusterNodes() as $sensorNode) {
            $this->addExpression(
                sprintf(
                    "plot%d = plot(%s, %s, 'd', 'MarkerSize', 8, 'MarkerEdgeColor', 'k', 'MarkerFaceColor', 'b');",
                    ++$plotIndex,
                    $this->convertCoordinate($sensorNode->getX()),
                    $this->convertCoordinate($sensorNode->getY())
                )
            );
        }

        $this->addNewLine();

        $this->addExpression('hold off;');

        $this->addExpression(sprintf('axis([0 %d 0 %d]);', $this->fieldSizeX, $this->fieldSizeY));

        return $this->content;
    }

    /**
     * @param int $rounds
     *
     * @return string
     */
    private function getFilePath(int $rounds): string
    {
        return __DIR__ . sprintf('/../var/matlab/plot_network_%d_rounds.m', $rounds);
    }

    /** @param string $expression */
    private function addExpression(string $expression)
    {
        $this->content .= $expression;

        $this->addNewLine();
    }

    /** @param string $comment */
    private function addComment(string $comment)
    {
        $this->content .= sprintf('%% %s', $comment);

        $this->addNewLine();
    }

    /** @return void */
    private function addNewLine()
    {
        $this->content .= "\n";
    }

    /**
     * @param int $coordinate
     *
     * @return string
     */
    private function convertCoordinate(int $coordinate): string
    {
        return (string) $coordinate / 10;
    }
}
