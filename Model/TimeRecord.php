<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;


use App\Entity\Timesheet;
use App\Entity\User;
use DateTime;

/**
 * Class to represent the view time records.
 */
class TimeRecord
{

    const VALID_MERGE_MODES = [
        RecordMergeMode::MODE_MERGE,
        RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY,
        RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY,
    ];

    /**
     * Create time record of timesheet entity.
     * @param Timesheet $timesheet
     * @param string $mergeMode
     * @return TimeRecord
     */
    public static function fromTimesheet(Timesheet $timesheet, $mergeMode = RecordMergeMode::MODE_MERGE): TimeRecord {
        if ( !in_array($mergeMode, self::VALID_MERGE_MODES) ) {
            throw new \InvalidArgumentException("Invalid merge mode given: $mergeMode");
        }

        return (new TimeRecord($timesheet->getBegin(), $timesheet->getUser(), $mergeMode))
            ->addTimesheet($timesheet);
    }

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $description = null;

    /**
     * @var float[]
     */
    private $hourlyRates = [];

    /**
     * @var float
     */
    private $rate = 0.0;

    /**
     * @var int
     */
    private $duration = 0;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $mergeMode;

    /**
     * Private constructor, use fromTimesheet() to create instances.
     * @param DateTime $date
     * @param User $user
     * @param string $mergeMode
     */
    private function __construct(DateTime $date, User $user, string $mergeMode)
    {
        $this->date = $date;
        $this->user = $user;
        $this->mergeMode = $mergeMode;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return int[]
     */
    public function getHourlyRates()
    {
        return $this->hourlyRates;
    }

    /**
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    // Helper methods

    public function hasDifferentHourlyRates()
    {
        return count($this->hourlyRates) > 1;
    }

    public function addTimesheet(Timesheet $timesheet)
    {
        $this->addHourlyRate($timesheet->getHourlyRate(), $timesheet->getDuration())
            ->addRate($timesheet->getRate())
            ->addDuration($timesheet->getDuration())
            ->setDescription($timesheet);

        return $this;
    }

    protected function addHourlyRate($hourlyRate, $duration)
    {
        if ( $hourlyRate > 0 && $duration > 0 ) {
            $entryIndex = null;
            foreach ($this->hourlyRates as $index => $info) {
                if ($info['hourlyRate'] === $hourlyRate) {
                    $entryIndex = $index;
                    break;
                }
            }

            if ($entryIndex === null) {
                $this->hourlyRates[] = [
                    'hourlyRate' => $hourlyRate,
                    'duration' => $duration,
                ];
            } else {
                $this->hourlyRates[$entryIndex]['duration'] += $duration;
            }
        }

        return $this;
    }

    private function addRate(?float $rate)
    {
        if ( $rate !== null ) {
            $this->rate += $rate;
        }

        return $this;
    }

    private function addDuration(?int $duration)
    {
        if ( $duration !== null ) {
            $this->duration += $duration;
        }

        return $this;
    }

    protected function setDescription(Timesheet $timesheet)
    {
        $description = $timesheet->getDescription();

        // Merge description dependent on record merge mode
        if ($this->description === null) {
            $this->description = $description;
        } else if ($this->mergeMode === RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY && $this->getDate() < $timesheet->getBegin()) {
            // Override description on last
            $this->description = $timesheet->getDescription();
        } else if ($this->mergeMode === RecordMergeMode::MODE_MERGE) {
            // MODE_MERGE
            if ($description !== null && strlen($description) > 0) {
                $this->description = (
                   implode(PHP_EOL, [
                        $this->getDescription(),
                        $description
                    ])
                );
            }
        }

        return $this;
    }

}