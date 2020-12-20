<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Service;


use App\Repository\Query\BaseQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use DateInterval;
use DateTime;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\ChartStat;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\TimeRecord;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class ViewService
{

    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var NativePasswordEncoder
     */
    private $encoder;

    /**
     * ViewService constructor.
     * @param TimesheetRepository $timesheetRepository
     * @param SessionInterface $session
     * @param PasswordEncoderInterface|null $encoder
     */
    public function __construct(TimesheetRepository $timesheetRepository, SessionInterface $session, ?PasswordEncoderInterface $encoder = null)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->session = $session;
        $this->encoder = ($encoder instanceof PasswordEncoderInterface) ? $encoder : new NativePasswordEncoder();
    }

    /**
     * Check if the user has access to the given shared project timesheet.
     * @param SharedProjectTimesheet $sharedProject
     * @param $givenPassword
     * @return bool
     */
    public function hasAccess(SharedProjectTimesheet $sharedProject, $givenPassword): bool
    {
        $hashedPassword = $sharedProject->getPassword();

        if ($hashedPassword !== null) {
            // Check session
            $projectId = $sharedProject->getProject()->getId();
            $shareKey = $sharedProject->getShareKey();
            $passwordMd5 = md5($hashedPassword);

            $sessionPasswordKey = "spt-authed-$projectId-$shareKey-$passwordMd5";

            if (!$this->session->has($sessionPasswordKey)) {
                // Check given password
                if (empty($givenPassword) || !$this->encoder->isPasswordValid($hashedPassword, $givenPassword, null)) {
                    return false;
                }

                $this->session->set($sessionPasswordKey, true);
            }
        }

        return true;
    }

    /**
     * Delivers time records for the given shared project timesheet and time span.
     * @param SharedProjectTimesheet $sharedProject
     * @param int $year
     * @param int $month
     * @return TimeRecord[]
     * @throws \Exception
     *
     * @todo Unit test
     */
    public function getTimeRecords(SharedProjectTimesheet $sharedProject, int $year, int $month): array
    {
        $month = max(min($month, 12), 1);

        $begin = new DateTime($year . '-' . $month . '-01 00:00:00');
        $end = clone $begin;
        $end->add(new DateInterval('P1M'));

        $query = new TimesheetQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->addProject($sharedProject->getProject());
        $query->setOrderBy('begin');
        $query->setOrder(BaseQuery::ORDER_ASC);

        $timesheets = $this->timesheetRepository->getTimesheetsForQuery($query);

        // Filter time records by merge mode
        $timeRecords = [];
        $mergeMode = $sharedProject->getRecordMergeMode();
        foreach ($timesheets as $timesheet) {
            $dateKey = $timesheet->getBegin()->format("Y-m-d");
            if (!array_key_exists($dateKey, $timeRecords)) {
                $timeRecords[$dateKey] = [];
            }

            $userKey = preg_replace("/[^a-z0-9]/", "", strtolower($timesheet->getUser()->getDisplayName()));
            if ($mergeMode !== RecordMergeMode::MODE_NONE) {
                // Assume that records from one user will be merged into one
                if (!array_key_exists($userKey, $timeRecords[$dateKey])) {
                    $timeRecords[$dateKey][$userKey] = [TimeRecord::fromTimesheet($timesheet, $mergeMode)];
                } else {
                    $timeRecords[$dateKey][$userKey][0]->addTimesheet($timesheet);
                }
            } else {
                // One user can be assigned to multiple records per day
                $time = $timesheet->getBegin()->format("H-i-s");
                $timeRecords[$dateKey][$userKey][$time] = TimeRecord::fromTimesheet($timesheet);
            }
        }

        // Sort records and create a flat, sorted list of records
        $flattenedTimeRecords = [];

        ksort($timeRecords);
        foreach($timeRecords as $recordsOfDate) {
            ksort($recordsOfDate);
            foreach ($recordsOfDate as $recordsOfUser) {
                ksort($recordsOfUser);
                foreach($recordsOfUser as $record) {
                    $flattenedTimeRecords[] = $record;
                }
            }
        }

        return $flattenedTimeRecords;
    }

    /**
     * Delivers stats for the given year (e.g. duration per month).
     * @param SharedProjectTimesheet $sharedProject
     * @param int $year
     * @return ChartStat[] stats per month, one-based index (1 - 12)
     *
     * @todo Unit test
     */
    public function getAnnualStats(SharedProjectTimesheet $sharedProject, int $year): array
    {
        $result = $this->timesheetRepository->createQueryBuilder('t')
            ->select([
                'YEAR(t.begin) as year',
                'MONTH(t.begin) as month',
                'SUM(t.duration) as duration',
                'SUM(t.rate) as rate',
            ])
            ->where('t.project = :project')
            ->andWhere('YEAR(t.begin) = :year')
            ->groupBy('year')
            ->addGroupBy('month')
            ->setParameters([
                'project' => $sharedProject->getProject(),
                'year' => $year,
            ])
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[(int) $row['month']] = new ChartStat($row);
        }

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($stats[$i])) {
                $stats[$i] = new ChartStat();
            }
        }

        ksort($stats);
        return $stats;
    }

    /**
     * Delivers stats for the given month (e.g. duration per day).
     * @param SharedProjectTimesheet $sharedProject
     * @param int $year
     * @param int $month
     * @return ChartStat[] stats per day
     *
     * @todo Unit test
     */
    public function getMonthlyStats(SharedProjectTimesheet $sharedProject, int $year, int $month): array
    {
        $result = $this->timesheetRepository->createQueryBuilder('t')
            ->select([
                'YEAR(t.begin) as year',
                'MONTH(t.begin) as month',
                'DAY(t.begin) as day',
                'SUM(t.duration) as duration',
                'SUM(t.rate) as rate',
            ])
            ->where('t.project = :project')
            ->andWhere('YEAR(t.begin) = :year')
            ->andWhere('MONTH(t.begin) = :month')
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('day')
            ->setParameters([
                'project' => $sharedProject->getProject(),
                'year' => $year,
                'month' => $month
            ])
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[(int) $row['day']] = new ChartStat($row);
        }

        $numberOfDays = date('t', (new DateTime("$year-$month-01"))->getTimestamp());
        for ($i = 1; $i <= $numberOfDays; $i++) {
            if (!isset($stats[$i])) {
                $stats[$i] = new ChartStat();
            }
        }

        ksort($stats);
        return $stats;
    }

}
