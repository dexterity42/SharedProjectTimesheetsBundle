<?php

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Form;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ManageService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SharedProjectFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'label' => 'shared_project_timesheets.manage.form.project',
                'required' => true,
                'class' => Project::class,
                'choice_label' => 'name',
                'query_builder' => function(ProjectRepository $projectRepository) {
                    return $projectRepository->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->add('password', PasswordType::class, [
                'label' => 'shared_project_timesheets.manage.form.password',
                'required' => false,
                'always_empty' => false,
                'mapped' => false,
            ])
            ->add('entryUserVisible', CheckboxType::class, [
                'label' => 'shared_project_timesheets.manage.form.entry_user_visible',
                'required' => false,
            ])
            ->add('entryRateVisible', CheckboxType::class, [
                'label' => 'shared_project_timesheets.manage.form.entry_rate_visible',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'shared_project_timesheets.manage.form.save',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SharedProjectTimesheet::class,
        ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($form->get('password')->getData())) {
            $view['password']->vars['value'] = ManageService::PASSWORD_DO_NOT_CHANGE_VALUE;
        }
    }

}
