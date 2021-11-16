<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\tests\Model;


use KimaiPlugin\SharedProjectTimesheetsBundle\Model\ChartStat;
use PHPUnit\Framework\TestCase;

class ChartStatTest extends TestCase
{

    public function testDefault(): void
    {
        $chartStat = new ChartStat();
        self::assertEquals(0, $chartStat->getDuration());
        self::assertEquals(0, $chartStat->getRate());
    }

    public function testValidRow(): void
    {
        $chartStat = new ChartStat([
            'duration' => 1,
            'rate' => 2.2,
        ]);
        self::assertEquals(1, $chartStat->getDuration());
        self::assertEquals(2.2, $chartStat->getRate());
    }

}