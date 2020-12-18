<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\tests\Model;


use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;
use PHPUnit\Framework\TestCase;

class RecordMergeModeTest extends TestCase
{

    public function testSize(): void
    {
        self::assertCount(4, RecordMergeMode::getModes());
    }

}