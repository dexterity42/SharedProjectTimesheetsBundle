<?php
/*
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;


class ChartStat
{

    /**
     * @var array
     */
    private $duration;

    /**
     * @var array
     */
    private $rate;

    public function __construct(?array $resultRow = null)
    {
        $this->duration = $resultRow !== null && isset($resultRow['duration']) ? $resultRow['duration'] : 0;
        $this->rate = $resultRow !== null && isset($resultRow['rate']) ? $resultRow['rate'] : 0;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @return mixed
     */
    public function getRate()
    {
        return $this->rate;
    }

}