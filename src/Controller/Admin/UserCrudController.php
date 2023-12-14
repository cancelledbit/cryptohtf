<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Vault\VaultHandler;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private Security               $security,
        private VaultHandler           $vault,
        private EntityManagerInterface $em,
        private RequestStack           $stack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name'),
            TextField::new('email'),
            AssociationField::new('personalVault'),
        ];
    }

    public function configureCrud(Crud $crud): Crud {
        return parent::configureCrud($crud)->showEntityActionsInlined();
    }


    public function configureActions(Actions $actions): Actions {

        return Actions::new()
            ->add(Crud::PAGE_INDEX, Action::new('removeUser', 'Удалить пользователя')
                ->linkToCrudAction('removeUser')
            )
            ->add(Crud::PAGE_INDEX, Action::new('removeVault', 'Удалить хранилище')
                ->linkToCrudAction('removeVault')
            )
            ->add(Crud::PAGE_INDEX, Action::new('loginAt', 'Войти под юзером')
                ->linkToCrudAction('loginAt')
            )
        ;
    }

    public function removeVault(AdminContext $context): Response {
        $userToRemoveVault = $context->getEntity()->getInstance();
        if (!$userToRemoveVault instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        $this->vault->setUser($userToRemoveVault);
        $this->vault->remove();
        $this->stack->getSession()->getFlashBag()->add('success', "{$userToRemoveVault->getName()} хранилище очищено");
        return $this->redirect('/admin');
    }

    public function removeUser(AdminContext $context): Response {
        $userToRemove = $context->getEntity()->getInstance();
        if (!$userToRemove instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        $this->vault->setUser($userToRemove);
        $this->vault->remove();
        $this->em->remove($userToRemove);
        $this->em->flush();
        $this->stack->getSession()->getFlashBag()->add('success', "{$userToRemove->getName()} удален");
        return $this->redirect('/admin');
    }

    public function loginAt(AdminContext $context): Response {
        $user = $context->getEntity()->getInstance();
        if (!$user instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        $this->security->login($user);
        $this->stack->getSession()->getFlashBag()->add('success', "Вход под пользователем {$user->getName()}");
        return $this->redirectToRoute('app_vault');
    }

}