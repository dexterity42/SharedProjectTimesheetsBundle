<?php


namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;


use App\Entity\Timesheet;
use App\Entity\User;
use DateTime;

/**
 * Class to represent the view time records.
 */
class TimeRecord
{

    /**
     * Create time record of timesheet entity.
     * @param Timesheet $timesheet
     * @param string $mergeMode MergeRecordMode
     * @return TimeRecord
     */
    public static function fromTimesheet(Timesheet $timesheet, $mergeMode): TimeRecord {
        return (new TimeRecord($timesheet->getBegin(), $timesheet->getUser()))
            ->addTimesheet($timesheet, $mergeMode);
    }

    /**
     * Private constructor, use fromTimesheet() to create instances.
     * @param DateTime $date
     * @param User $user
     */
    private function __construct(DateTime $date, User $user)
    {
        $this->date = $date;
        $this->user = $user;
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

    public function addTimesheet(Timesheet $timesheet, $mergeMode)
    {
        $this->addHourlyRate($timesheet->getHourlyRate(), $timesheet->getDuration())
            ->addRate($timesheet->getRate())
            ->addDuration($timesheet->getDuration())
            ->setDescription($timesheet, $mergeMode);

        return $this;
    }

    protected function addHourlyRate($hourlyRate, $duration)
    {
        $entry = null;
        foreach ($this->hourlyRates as $info) {
            if ( $info['hourlyRate'] === $hourlyRate ) {
                $entry = $info;
                break;
            }
        }

        if ( $entry === null ) {
            $this->hourlyRates[] = [
                'hourlyRate' => $hourlyRate,
                'duration' => $duration,
            ];
        } else {
            $entry['duration'] += $duration;
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

    protected function setDescription(Timesheet $timesheet, $mergeMode)
    {
        $description = $timesheet->getDescription();

        // Merge description dependent on record merge mode
        if ( $this->description === null ) {
            $this->description = $description;
        } else if ( $mergeMode === MergeRecordMode::MODE_MERGE_USE_LAST_OF_DAY && $this->getDate() < $timesheet->getBegin() ) {
            // Override description on last
            $this->description = $timesheet->getDescription();
        } else if ( $mergeMode === MergeRecordMode::MODE_MERGE) {
            // MODE_MERGE
            if ( $description !== null && strlen($description) > 0 ) {
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