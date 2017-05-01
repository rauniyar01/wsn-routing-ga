<?php

namespace Podorozhny\Dissertation;

class NetworkExporter
{
    /** @var string */
    private $content;

    public function export(Network $network)
    {
        $fileName = $this->getFilePath();

        $directory = dirname($fileName);

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

        $this->addExpression(
            sprintf(
                "plot(%s, %s, 'or')",
                $this->convertCoordinate($baseStation->getX()),
                $this->convertCoordinate($baseStation->getY())
            )
        );

        foreach ($network->getClusterHeads() as $node) {
            $this->addExpression(
                sprintf(
                    "plot(%s, %s, '+r')",
                    $this->convertCoordinate($node->getX()),
                    $this->convertCoordinate($node->getY())
                )
            );
        }

        foreach ($network->getClusterNodes() as $node) {
            $this->addExpression(
                sprintf(
                    "plot(%s, %s, '+b')",
                    $this->convertCoordinate($node->getX()),
                    $this->convertCoordinate($node->getY())
                )
            );

            $this->addExpression(
                sprintf(
                    "plot([%s %s], [%s %s], '-b')",
                    $this->convertCoordinate($node->getX()),
                    $this->convertCoordinate($node->getClusterHead()->getX()),
                    $this->convertCoordinate($node->getY()),
                    $this->convertCoordinate($node->getClusterHead()->getY())
                )
            );
        }

        $this->addExpression('hold off');

        $this->addExpression(sprintf('axis([0 %d 0 %d])', FIELD_SIZE, FIELD_SIZE));

        return $this->content;
    }

    private function getFilePath(): string
    {
        return __DIR__ . '/../var/matlab/plot_network.m';
    }

    /**
     * @param string $expression
     */
    private function addExpression(string $expression)
    {
        $this->content .= $expression . "\n";
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
