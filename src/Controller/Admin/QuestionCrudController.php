<?php

namespace App\Controller\Admin;

use App\Entity\Question;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class QuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Question::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();
        yield Field::new('name');
        yield AssociationField::new('topic');
        yield TextareaField::new('question')
            ->hideOnIndex();
        yield Field::new('votes', 'Total Votes')
        ->setTextAlign('right');
//        ->setLabel('Total Votes');
        yield AssociationField::new('askedBy')
        ->autocomplete()
        ->formatValue(static function($value, ?Question $question){
            if(!$user = $question->getAskedBy()){
                return null;
            }

            return sprintf('%s&nbsp;(%s)', $user->getEmail(), $user->getQuestions()->count());
        })
        ->setQueryBuilder(function (QueryBuilder $queryBuilder){
            $queryBuilder->andWhere('entity.enabled = :enabled')
            ->setParameter('enabled', true);
        });
        yield AssociationField::new('answers')
//        ->setFormTypeOption('choice_label', 'id') // does not work with autocomplete
        ->setFormTypeOption('by_reference', false)
        ->autocomplete();

        yield Field::new('createdAt')
        ->hideOnForm();

    }

/*    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'askedBy.enabled' => 'DESC',
                'createdAt' => 'DESC',
            ]);
    }*/

}
