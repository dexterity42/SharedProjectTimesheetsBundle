<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ViewService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/auth/shared-project-timesheets")
 */
class ViewController extends AbstractController
{

    /**
     * @var ViewService
     */
    private $viewService;

    /**
     * @var SharedProjectTimesheetRepository
     */
    private $sharedProjectTimesheetRepository;

    /**
     * @param ViewService $viewService
     * @param SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
     */
    public function __construct(
        ViewService $viewService,
        SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
    ) {
        $this->viewService = $viewService;
        $this->sharedProjectTimesheetRepository = $sharedProjectTimesheetRepository;
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
        // Receive parameters.
        $givenPassword = $request->get('spt-password');
        $year = (int)$request->get('year', date('Y'));
        $month = (int)$request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');

        // Get project.
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

        // Check access.
        if (!$this->viewService->hasAccess($sharedProject, $givenPassword)) {
            return $this->render(
                '@SharedProjectTimesheets/view/auth.html.twig',
                [
                    'project' => $sharedProject->getProject(),
                    'invalidPassword' => $request->isMethod("POST") && $givenPassword !== null,
                ]
            );
        }

        // Get time records.
        $timeRecords = $this->viewService->getTimeRecords($sharedProject, $year, $month);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Define currency.
        $currency = 'EUR';
        $customer = $sharedProject->getProject()->getCustomer();
        if ( $customer !== null ) {
            $currency = $customer->getCurrency();
        }

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();

        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedProject, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedProject, $year, $month) : null;

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
                'statsPerMonth' => $statsPerMonth,
                'monthlyChartVisible' => $monthlyChartVisible,
                'statsPerDay' => $statsPerDay,
                'detailsMode' => $detailsMode,
            ]
        );
    }

}
