<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn():string
    {
        return User::class;
    }


    public function configureFields(string $pageName):iterable
    {
        /* return [
             IdField::new('id'),
             TextField::new('title'),
             TextEditorField::new('description'),
         ]; // OR :  */
        yield IdField::new('id')
            ->onlyOnIndex();
        yield ImageField::new('avatar')
        ->setBasePath('uploads/avatars')
        ->setUploadDir('public/uploads/avatars')
        ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
//        ->setFormTypeOption('upload_new', function()) // Add login to save in S3 service
        ;
        yield EmailField::new('email');
        yield TextField::new('fullName') //it works because User::getFullName exists
        ->hideOnForm();
        yield TextField::new('firstName')
            ->onlyOnForms();
        yield TextField::new('lastName')
            ->onlyOnForms();
        yield BooleanField::new('enabled')
            ->renderAsSwitch(false);
        yield DateField::new('createdAt')
        ->hideOnForm();
        $roles = [         'ROLE_SUPER_ADMIN',  'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER' ];
        yield ChoiceField::new('roles')
        ->setHelp('Available roles: ROLE ....')
//        ->setFormType(ChoiceType::class)
       /* ->setFormTypeOptions([
            'choices' => array_combine($roles, $roles),
            'multiple' => true,
            'expanded' => true,
                             ])*/ // not needs
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges()
        ;


    }

}
