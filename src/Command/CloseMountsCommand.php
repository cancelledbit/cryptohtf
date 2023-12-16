<?php

namespace App\Command;

use App\Entity\PersonalVault;
use App\Entity\User;
use App\Service\Vault\VaultFactory;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:close-mounts',
    description: 'Add a short description for your command',
)]
class CloseMountsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private VaultFactory $handler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $expressionBuilder = Criteria::expr();

        $criteria = new Criteria();
        $criteria->where($expressionBuilder->neq('mountPoint', null));
        $criteria->andWhere($expressionBuilder->neq('mountPoint', ''));

        $users= $this->em->getRepository(PersonalVault::class)->matching($criteria)->map(static fn(PersonalVault $vault): ?User => $vault->getOwner());
        $users->filter(static fn(?User $u): bool => $u !== null);
        foreach ($users as $user) {
            $vault = $this->handler->getByUser($user);
            if ($vault->isExpired()) {
                $vault->lock();
                $io->caution("Lock {$user->getName()}");
            }
        }

        return Command::SUCCESS;
    }
}
