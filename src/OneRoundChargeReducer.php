<?php

namespace Podorozhny\Dissertation;

class OneRoundChargeReducer
{
    /**
     * @param Network $network
     */
    public function reduce(Network $network)
    {
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
                $node->reduceCharge(0.025);
            }
        }
    }

    /**
     * @param int $distance
     *
     * @return int
     */
    private function getEnergyPercentFromDistance(int $distance): int
    {
        return $distance / 1000;
    }
}
