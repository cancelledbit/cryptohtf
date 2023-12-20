<?php

namespace App\Controller\Admin;

use App\Entity\PersonalVault;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PersonalVaultCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PersonalVault::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            AssociationField::new('owner'),
            TextField::new('CypherPoint'),
            TextField::new('MountPoint'),
            DateTimeField::new('LastMountTs'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return Actions::new();
    }
}
