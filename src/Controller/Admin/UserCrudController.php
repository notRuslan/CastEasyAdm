<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
       /* return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ]; // OR :  */
        yield IdField::new('id');
        yield EmailField::new('email');
        yield TextField::new('fullName'); //it works because User::getFullName exists
//        yield TextField::new('firstName');
//        yield TextField::new('lastName');
        yield BooleanField::new('enabled')
        ->renderAsSwitch(false);
        yield DateField::new('createdAt');

    }

}
