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

}
