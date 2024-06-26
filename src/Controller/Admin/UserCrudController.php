<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Vault\Exception\NoVaultException;
use App\Service\Vault\VaultFactory;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserCrudController extends AbstractCrudController
{
    private ?FlashBagInterface $flashBag;

    public function __construct(
        private Security $security,
        private VaultFactory $vaultFactory,
        private EntityManagerInterface $em,
        private RequestStack $stack,
    ) {
        $session = $this->stack->getSession();
        if (method_exists($session, 'getFlashBag')) {
            $this->flashBag = $session->getFlashBag();
        }
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

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
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
            ->add(Crud::PAGE_INDEX, Action::new('changeSecret', 'Сменить ключ хранилища')
                ->linkToRoute('changePassword', static fn (User $user): array => ['id' => $user->getId()])
            )
        ;
    }

    #[Route('/admin/change_secret', name: 'changePassword')]
    public function changePassword(Request $req): Response
    {
        $user = $this->em->find(User::class, $req->get('id'));
        $pass = null;
        $old = $req->get('oldSecret');
        if ($old) {
            $vault = $this->vaultFactory->getByUser($user);
            try {
                $pass = $vault->refresh($old);
            } catch (\Throwable $e) {
                $pass = $e->getMessage();
            }
        }
        if (!$user) {
            $this->flashBag?->add('error', 'Пользователь не найден');

            return $this->redirect('/admin');
        }

        return $this->render('admin/user/updatePassword.html.twig', [
            'user' => $user,
            'pass' => $pass,
        ]);
    }

    public function removeVault(AdminContext $context): Response
    {
        $userToRemoveVault = $context->getEntity()->getInstance();
        if (!$userToRemoveVault instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        try {
            $vault = $this->vaultFactory->getByUser($userToRemoveVault);
            $vault->remove();
        } catch (NoVaultException) {
        }
        $this->flashBag?->add('success', "{$userToRemoveVault->getName()} хранилище очищено");

        return $this->redirect('/admin');
    }

    public function removeUser(AdminContext $context): Response
    {
        $userToRemove = $context->getEntity()->getInstance();
        if (!$userToRemove instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        try {
            $vault = $this->vaultFactory->getByUser($userToRemove);
            $vault->remove();
        } catch (NoVaultException) {
        }

        $this->em->remove($userToRemove);
        $this->em->flush();
        $this->flashBag?->add('success', "{$userToRemove->getName()} удален");

        return $this->redirect('/admin');
    }

    public function loginAt(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();
        if (!$user instanceof User) {
            throw new \UnexpectedValueException('Unexpected entity type');
        }
        $this->security->login($user);
        $this->flashBag?->add('success', "Вход под пользователем {$user->getName()}");

        return $this->redirectToRoute('app_vault');
    }
}
