<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use App\Entity\User;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use function Sodium\add;

#[IsGranted('ROLE_MODERATOR')]
class QuestionCrudController extends AbstractCrudController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator,
                                private RequestStack      $requestStack
    )
    {

    }

    public static function getEntityFqcn():string
    {
        return Question::class;
    }


    public function configureFields(string $pageName):iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();
        yield FormField::addTab('Basic Data')
//            ->collapsible()
        ;
        yield Field::new('name')
            ->setSortable(false)
        ->setColumns(5);
        yield Field::new('slug')
            ->hideOnIndex()
            ->setFormTypeOption(
                'disabled',
                $pageName !== Crud::PAGE_NEW
            )
            ->setColumns(5);

        yield AssociationField::new('topic');
//        yield TextEditorField::new('question')
        yield TextareaField::new('question')
            ->hideOnIndex()
            ->setFormTypeOptions([
                                     'row_attr' => [
                                         'data-controller' => 'snarkdown'
                                     ],
                                     'attr'     => [
                                         'data-snarkdown-target' => 'input',
                                         'data-action'           => 'snarkdown#render'
                                     ]
                                 ])
            ->setHelp('Preview: ');
        yield VotesField::new('votes', 'Total votes')
            ->setTextAlign('right')
            ->setPermission('ROLE_SUPER_ADMIN');
        yield FormField::addTab('Details')
//            ->collapsible()
            ->setIcon('info')
            ->setHelp('Additional details');
        yield AssociationField::new('askedBy')
            ->autocomplete()
            ->formatValue(static function ($value, ?Question $question) {
                if (!$user = $question->getAskedBy()) {
                    return null;
                }

                return sprintf('%s&nbsp;(%s)', $user->getEmail(), $user->getQuestions()->count());
            })
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                $queryBuilder->andWhere('entity.enabled = :enabled')
                    ->setParameter('enabled', true);
            });
        yield AssociationField::new('answers')
//        ->setFormTypeOption('choice_label', 'id') // does not work with autocomplete
            ->setFormTypeOption('by_reference', false)
            ->autocomplete();

        yield Field::new('createdAt')
            ->hideOnForm();
        yield AssociationField::new('updatedBy')
            ->onlyOnDetail();

        yield Field::new('isApproved');
    }

    /*    public function configureCrud(Crud $crud): Crud
        {
            return parent::configureCrud($crud)
                ->setDefaultSort([
                    'askedBy.enabled' => 'DESC',
                    'createdAt' => 'DESC',
                ]);
        }*/
    public function configureCrud(Crud $crud):Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                                 'askedBy.enabled' => 'DESC',
                                 'createdAt'       => 'DESC',
                             ]);
    }

    public function configureActions(Actions $actions):Actions
    {
        /*$viewAction = Action::new('view')
        ->linkToUrl(function (Question $question){
           return $this->generateUrl('app_question_show', [
               'slug' => $question->getSlug(),
           ]);
        })
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-eye')
        ->setLabel('View on site');*/

        //Second way
        $viewAction = function () {
            return Action::new('view')
                ->linkToUrl(function (Question $question) {
                    return $this->generateUrl('app_question_show', [
                        'slug' => $question->getSlug(),
                    ]);
                })
                ->setIcon('fa fa-eye')
                ->setLabel('View on site');
        };
//--
        $approveAction = Action::new('approve')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check-circle')
            ->displayAsButton()
            ->setTemplatePath('admin/approve_action.html.twig')
            ->linkToCrudAction('approve')
            ->displayIf(static function (Question $question):bool {
                return !$question->getIsApproved();
            });

        $exportAction = Action::new('export')
//            ->linkToCrudAction('export')
            ->linkToUrl(function () {
                $request = $this->requestStack->getCurrentRequest();

                return $this->adminUrlGenerator
                    ->setAll($request->query->all()) // get all original params
                    ->setAction('export')
                    ->generateUrl();
            })
            ->createAsGlobalAction()
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-download');

        return parent::configureActions($actions)
            ->update(Crud::PAGE_INDEX, Action::DELETE, static function (Action $action) {
                $action->displayIf(static function (Question $question) {
//                    return !$question->getIsApproved();
                    return true;
                });

                return $action;
            })
            ->setPermission(Action::INDEX, 'ROLE_MODERATOR')
            ->setPermission(Action::DETAIL, 'ROLE_MODERATOR')
            ->setPermission(Action::EDIT, 'ROLE_MODERATOR')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->add(Crud::PAGE_DETAIL, $viewAction()->addCssClass('btn btn-success'))
            ->add(Crud::PAGE_INDEX, $viewAction())
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->reorder(Crud::PAGE_DETAIL, [
                'approve',
                'view',
                Action::EDIT,
                Action::INDEX,
                Action::DELETE
            ]);
//        ->add(Crud::PAGE_DETAIL, $viewAction)
//        ->add(Crud::PAGE_INDEX, $viewAction);
    }

    public function configureFilters(Filters $filters):Filters
    {
        return parent::configureFilters($filters)
            ->add('topic')
            ->add('createdAt')
            ->add('votes')
            ->add('name');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance):void
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Is it not User');
        }

        $entityInstance->setUpdatedBy($user);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @param Question $entityManager
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance):void
    {
        if ($entityInstance->getIsApproved()) {
            throw new \Exception('Deleting approved questions is forbidden!');
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function approve(AdminContext $adminContext, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $question = $adminContext->getEntity()->getInstance();
        if (!$question instanceof Question) {
            throw new \LogicException('Entity is missing or not a Question');
        }

        $question->setIsApproved(true);
        $entityManager->flush();

        $targetUrl = $adminUrlGenerator->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($question->getId())
            ->generateUrl();

        return $this->redirect($targetUrl);
    }

    public function export(AdminContext $context, CsvExporter $csvExporter)
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        return $csvExporter->createResponseFromQueryBuilder(
            $queryBuilder,
            $fields,
            'questions.csv'
        );
    }

}
