<?php

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Controller;

use App\Controller\AbstractController;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Form\SharedProjectFormType;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ManageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(path="/admin/shared-project-timesheets")
 * @Security("is_granted('ROLE_SUPER_ADMIN')")
 */
class ManageController extends AbstractController
{
    /**
     * @var SharedProjectTimesheetRepository
     */
    protected $shareProjectTimesheetRepository;

    /**
     * @var ManageService
     */
    private $manageService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param SharedProjectTimesheetRepository $shareProjectTimesheetRepository
     * @param ManageService $manageService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        SharedProjectTimesheetRepository $shareProjectTimesheetRepository,
        ManageService $manageService,
        TranslatorInterface $translator
    ) {
        $this->shareProjectTimesheetRepository = $shareProjectTimesheetRepository;
        $this->manageService = $manageService;
        $this->translator = $translator;
    }

    /**
     * @Route(path="", name="manage_shared_project_timesheets", methods={"GET"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        $sharedProjects = $this->shareProjectTimesheetRepository->findAll();

        return $this->render(
            '@SharedProjectTimesheets/manage/index.html.twig',
            [
                'sharedProjects' => $sharedProjects,
            ]
        );
    }

    /**
     * @Route(path="/create", name="create_shared_project_timesheets", methods={"GET","POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request)
    {
        $sharedProject = new SharedProjectTimesheet();

        $form = $this->createForm(SharedProjectFormType::class, $sharedProject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manageService->create($sharedProject, $form->get('password')->getData());
                $this->flashSuccess($this->translator->trans('shared_project_timesheets.manage.persist.success'));
                return $this->redirectToRoute('manage_shared_project_timesheets');
            } catch (OptimisticLockException | ORMException $e) {
                $this->logException($e);
                $this->flashError($this->translator->trans('shared_project_timesheets.manage.persist.error'));
            }
        }

        return $this->render(
            '@SharedProjectTimesheets/manage/edit.html.twig',
            [
                'form' => $form->createView(),
                'type' => 'create',
            ]
        );
    }

    /**
     * @Route(path="/{projectId}/{shareKey}", name="update_shared_project_timesheets", methods={"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request)
    {
        $projectId = $request->get('projectId');
        $shareKey = $request->get('shareKey');

        if ($projectId == null || $shareKey == null) {
            throw new NotFoundHttpException("Project not found");
        }

        /* @var $sharedProject SharedProjectTimesheet */
        $sharedProject = $this->shareProjectTimesheetRepository->findOneBy(['project' => $projectId, 'shareKey' => $shareKey]);
        if ($sharedProject === null) {
            throw new NotFoundHttpException("Project not found");
        }

        // Store data in temporary SharedProjectTimesheet object
        $form = $this->createForm(SharedProjectFormType::class, $sharedProject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manageService->update($sharedProject, $form->get('password')->getData());
                $this->flashSuccess($this->translator->trans('shared_project_timesheets.manage.persist.success'));
                return $this->redirectToRoute('manage_shared_project_timesheets');
            } catch (OptimisticLockException | ORMException $e) {
                $this->logException($e);
                $this->flashError($this->translator->trans('shared_project_timesheets.manage.persist.error'));
            }
        } else if ( !$form->isSubmitted() ) {
            if ( !empty($sharedProject->getPassword()) ) {
                $form->get('password')->setData(ManageService::PASSWORD_DO_NOT_CHANGE_VALUE);
            }
        }

        return $this->render(
            '@SharedProjectTimesheets/manage/edit.html.twig',
            [
                'form' => $form->createView(),
                'type' => 'update',
            ]
        );
    }

    /**
     * @Route(path="/{projectId}/{shareKey}/remove", name="remove_shared_project_timesheets", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function remove(Request $request)
    {
        $projectId = $request->get('projectId');
        $shareKey = $request->get('shareKey');

        if ($projectId == null || $shareKey == null) {
            throw new NotFoundHttpException("Project not found");
        }

        /* @var $sharedProject SharedProjectTimesheet */
        $sharedProject = $this->shareProjectTimesheetRepository->findOneBy(['project' => $projectId, 'shareKey' => $shareKey]);
        if (!$sharedProject || $sharedProject->getProject() === null || $sharedProject->getShareKey() === null) {
            throw new NotFoundHttpException("Project not found");
        }

        try {
            $this->shareProjectTimesheetRepository->remove($sharedProject);
            $this->flashSuccess($this->translator->trans('shared_project_timesheets.manage.persist.success'));
        } catch (OptimisticLockException | ORMException $e) {
            $this->logException($e);
            $this->flashError($this->translator->trans('shared_project_timesheets.manage.persist.error'));
        }

        return $this->redirectToRoute('manage_shared_project_timesheets');
    }

}
