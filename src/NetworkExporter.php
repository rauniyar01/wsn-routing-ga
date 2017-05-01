<?php

namespace Podorozhny\Dissertation;

use Symfony\Component\Finder\Finder;

class NetworkExporter
{
    /** @var string */
    private $content;

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
            foreach ((new Finder())->files()->in($directory) as $file) {
                /** @var \SplFileInfo $file */

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

        $this->addExpression('close all');
        $this->addExpression('hold on');

        $baseStation = $network->getBaseStation();

        $this->addNewLine();
        $this->addComment('Base station');

        $this->addExpression(
            sprintf(
                "plot(%s, %s, 'or')",
                $this->convertCoordinate($baseStation->getX()),
                $this->convertCoordinate($baseStation->getY())
            )
        );

        $this->addNewLine();
        $this->addComment('Cluster heads');

        foreach ($network->getClusterHeads() as $node) {
            if ($node->isDead()) {
                continue;
            }

            $this->addExpression(
                sprintf(
                    "plot(%s, %s, '+r')",
                    $this->convertCoordinate($node->getX()),
                    $this->convertCoordinate($node->getY())
                )
            );
        }

//        $this->addNewLine();
//        $this->addComment('Connections between cluster heads and base station');
//
//        foreach ($network->getClusterHeads() as $node) {
//            if ($node->isDead()) {
//                continue;
//            }
//
//            $this->addExpression(
//                sprintf(
//                    "plot([%s %s], [%s %s], '--k', 'LineWidth', 0.1)",
//                    $this->convertCoordinate($node->getX()),
//                    $this->convertCoordinate($baseStation->getX()),
//                    $this->convertCoordinate($node->getY()),
//                    $this->convertCoordinate($baseStation->getY())
//                )
//            );
//        }

        $this->addNewLine();
        $this->addComment('Cluster nodes');

        foreach ($network->getClusterNodes() as $node) {
            if ($node->isDead()) {
                continue;
            }

            $this->addExpression(
                sprintf(
                    "plot(%s, %s, '+b')",
                    $this->convertCoordinate($node->getX()),
                    $this->convertCoordinate($node->getY())
                )
            );
        }

//        $this->addNewLine();
//        $this->addComment('Connections between cluster nodes and heads');
//
//        foreach ($network->getClusterNodes() as $node) {
//            if ($node->isDead()) {
//                continue;
//            }
//
//            $this->addExpression(
//                sprintf(
//                    "plot([%s %s], [%s %s], '--b', 'LineWidth', 0.1)",
//                    $this->convertCoordinate($node->getX()),
//                    $this->convertCoordinate($node->getClusterHead()->getX()),
//                    $this->convertCoordinate($node->getY()),
//                    $this->convertCoordinate($node->getClusterHead()->getY())
//                )
//            );
//        }

        $this->addNewLine();

        $this->addExpression('hold off');

        $this->addExpression(sprintf('axis([0 %d 0 %d])', FIELD_SIZE, FIELD_SIZE));

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

    /**
     * @param string $expression
     */
    private function addExpression(string $expression)
    {
        $this->content .= $expression;

        $this->addNewLine();
    }

    /**
     * @param string $comment
     */
    private function addComment(string $comment)
    {
        $this->content .= sprintf('%% %s', $comment);

        $this->addNewLine();
    }

    /**
     * @return void
     */
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
