<?php

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Repository\TimesheetRepository;
use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
     * @param UserPasswordEncoderInterface $encoder
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
                if ($this->encoder->isPasswordValid($hashedPassword, $givenPassword, null)) {
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

        $begin = new DateTime($year.'-'.$month.'-01 00:00:00');
        $end = new DateTime($year.'-'.($month % 12 + 1).'-01 00:00:00');

        $timeRecords = $this->timesheetRepository->createQueryBuilder("t")
            ->innerJoin(Project::class, "p", Join::WITH, "p.id = t.project")
            ->where("p.id = :project")
            ->andWhere("t.begin BETWEEN :begin AND :end")
            ->andWhere("t.end IS NOT NULL")
            ->orderBy("t.begin", "DESC")
            ->setParameters(
                [
                    'project' => $sharedProject->getProject(),
                    'begin' => $begin,
                    'end' => $end,
                ]
            )
            ->getQuery()
            ->execute();

        $rateSum = 0;
        $durationSum = 0;
        foreach ($timeRecords as $timeRecord) {
            $rateSum += $timeRecord->getRate();
            $durationSum += $timeRecord->getDuration();
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
            ]
        );
    }

}
