<?php

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use DateInterval;
use DateTime;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\MergeRecordMode;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\TimeRecord;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * @Route(path="/auth/shared-project-timesheets")
 */
class ViewController extends AbstractController
{

    /**
     * @var SharedProjectTimesheetRepository
     */
    private $sharedProjectTimesheetRepository;

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
     * @param SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
     * @param TimesheetRepository $timesheetRepository
     * @param SessionInterface $session
     */
    public function __construct(
        SharedProjectTimesheetRepository $sharedProjectTimesheetRepository,
        TimesheetRepository $timesheetRepository,
        SessionInterface $session
    ) {
        $this->sharedProjectTimesheetRepository = $sharedProjectTimesheetRepository;
        $this->session = $session;
        $this->timesheetRepository = $timesheetRepository;

        $this->encoder = new NativePasswordEncoder();
    }

    /**
     * @Route(path="/{projectId}/{shareKey}", name="view_shared_project_timesheets", methods={"GET","POST"})
     * @param string $projectId
     * @param string $shareKey
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(string $projectId, string $shareKey, Request $request)
    {
        $sharedProject = $this->sharedProjectTimesheetRepository->findByProjectAndShareKey(
            $projectId,
            $shareKey
        );

        if ($sharedProject === null) {
            throw new NotFoundHttpException("Project not found.");
        }

        if ($sharedProject->getShareKey() !== $shareKey) {
            return $this->render(
                '@SharedProjectTimesheets/view/error.html.twig',
                ['error' => 'shared_project_timesheets.view.error.access_denied']
            );
        }

        // Check password if set.
        $hashedPassword = $sharedProject->getPassword();
        $givenPassword = $request->get("spt-password", null);

        if ($hashedPassword !== null) {
            // Check session
            $passwordMd5 = md5($hashedPassword);
            $sessionPasswordKey = "spt-authed-$projectId-$shareKey-$passwordMd5";

            if (!$this->session->has($sessionPasswordKey)) {
                // Check given password
                if (!empty($givenPassword) && $this->encoder->isPasswordValid($hashedPassword, $givenPassword, null)) {
                    $this->session->set($sessionPasswordKey, true);
                } else {
                    return $this->render(
                        '@SharedProjectTimesheets/view/auth.html.twig',
                        [
                            'project' => $sharedProject->getProject(),
                            'invalidPassword' => $request->isMethod("POST") && $givenPassword !== null,
                        ]
                    );
                }
            }
        }

        $year = (int)$request->get("year", date('Y'));
        $month = (int)$request->get("month", date('m'));

        $month = max(min($month, 12), 1);

        $begin = new DateTime($year . '-' . $month . '-01 00:00:00');
        $end = clone $begin;
        $end->add(new DateInterval('P1M'));

        $query = new TimesheetQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->addProject($sharedProject->getProject());
        $timesheets = $this->timesheetRepository->getTimesheetsForQuery($query);

        /* @var $timeRecords TimeRecord[] */
        $timeRecords = [];

        // Filter time records by merge mode
        $mergeMode = $sharedProject->getRecordMergeMode();
        foreach ($timesheets as $timesheet) {
            if ($mergeMode !== MergeRecordMode::MODE_NONE) {
                $key = $timesheet->getBegin()->format("Y-m-d");
                if (!array_key_exists($key, $timeRecords)) {
                    $timeRecords[$key] = TimeRecord::fromTimesheet($timesheet, $mergeMode);
                } else {
                    $timeRecords[$key]->addTimesheet($timesheet, $mergeMode);
                }
            } else {
                $timeRecords[] = TimeRecord::fromTimesheet($timesheet, $mergeMode);
            }
        }

        $rateSum = 0;
        $durationSum = 0;
        foreach ($timeRecords as $timeRecord) {
            $rateSum += $timeRecord->getRate();
            $durationSum += $timeRecord->getDuration();
        }

        $currency = 'EUR';
        $customer = $sharedProject->getProject()->getCustomer();
        if ( $customer !== null ) {
            $currency = $customer->getCurrency();
        }

        return $this->render(
            '@SharedProjectTimesheets/view/timesheet.html.twig',
            [
                'sharedProject' => $sharedProject,
                'timeRecords' => $timeRecords,
                'rateSum' => $rateSum,
                'durationSum' => $durationSum,
                'year' => $year,
                'month' => $month,
                'currency' => $currency,
            ]
        );
    }

}
