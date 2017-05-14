<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

class Util
{
    /**
     * @param array $items
     *
     * @return mixed random key
     */
    public static function arrayRand(array $items)
    {
        Assert::that(count($items))->greaterThan(0, 'Can not pick random element from empty array.');

        $keys = array_keys($items);

        return array_slice($keys, mt_rand(0, count($keys) - 1), 1)[0];
    }

    /** @param array $items */
    public static function shuffle(array &$items)
    {
        Assert::that(count($items))->greaterThan(0, 'Can not shuffle empty array.');

        $order = array_map(
            function () {
                return mt_rand();
            },
            range(1, count($items))
        );

        array_multisort($order, $items);
    }

    /**
     * @param int    $n
     * @param string $form1
     * @param string $form2
     * @param string $form3
     *
     * @return string
     */
    public static function pluralForm(int $n, string $form1, string $form2, string $form3): string
    {
        if ($n % 10 === 1 && $n % 100 !== 11) {
            return $form1;
        }

        if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
            return $form2;
        }

        return $form3;
    }
}
