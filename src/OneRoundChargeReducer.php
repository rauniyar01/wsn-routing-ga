<?php

namespace Podorozhny\Dissertation;

class OneRoundChargeReducer
{
    /**
     * @param Network $network
     */
    public function reduce(Network $network)
    {
        $baseStation = $network->getBaseStation();

        // Базовая станция говорит всем узлам информацию о том, являются ли они головными
        foreach ($network->getNodes() as $node) {
            $node->reduceCharge(0.005);
        }

        // Головные узлы вещают всем подрят свой id
        // Обычные узлы выбирают головной узел с максимальным RSSI
        foreach ($network->getNodes() as $node) {
            $node->reduceCharge(0.005);
        }

        // Обычные узлы сообщают выбранному головному узлу что они являются его подопечными
        foreach ($network->getNodes() as $node) {
            $node->reduceCharge(0.005);
        }

        // Головной узел генерирует TDMA-расписание и рассылает своим подопечным
        // Подопечные сохраняют расписание и засыпают ожидая свои временные интервалы
        foreach ($network->getNodes() as $node) {
            $node->reduceCharge(0.005);
        }

        for ($j = 0; $j < ROUND_ITERATIONS_COUNT; $j++) {
            // Затраты обычных узлов на передачу информации головным
            // Затраты головных узлов на прием информации с подопечных
            foreach ($network->getClusterNodes() as $node) {
                $distance = $node->distanceToNeighbor($node->getClusterHead());
                $energy   = $this->getEnergyPercentFromDistance($distance);

                $node->reduceCharge($energy);
                $node->getClusterHead()->reduceCharge($energy);
            }

            // Затраты головных узлов на отправку большого пакета на базовую станцию
            foreach ($network->getClusterHeads() as $node) {
                $distance = $node->distanceTo($baseStation->getX(), $baseStation->getY());
                $energy   = $this->getEnergyPercentFromDistance($distance);

                // abstract multiplier based on packet size
                $energy *= 2;

                $node->reduceCharge($energy);
            }
        }
    }

    /**
     * @param int $distance
     *
     * @return float
     */
    private function getEnergyPercentFromDistance(int $distance): float
    {
        return $distance / 1000;
    }
}
