<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:new-user',
    description: 'Add a short description for your command',
)]
class NewUserCommand extends Command
{
    public function __construct(private UserRepository $repository, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('first_name', InputArgument::REQUIRED, 'Argument description')
			->addArgument('last_name', InputArgument::REQUIRED, 'Argument description')
			->addArgument('email', InputArgument::REQUIRED, 'Argument description')
			->addArgument('role', InputArgument::OPTIONAL, default: 'ROLE_USER')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $firstName = $input->getArgument('first_name');
		$lastName = $input->getArgument('last_name');
		$email = $input->getArgument('email');
		$role = $input->getArgument('role');
		if (!$firstName || !$lastName || !$email) {
			return Command::FAILURE;
		}

		$users = $this->repository->findBy(['email' => $email]);
		if (!count($users)) {
			$user = new User();
		} else {
			$user = reset($users);
		}

		$user->setEmail($email);
		$user->setName($firstName, $lastName);
		$user->setRoles([$role]);
		$pwd = substr(md5(random_bytes(8)), 10);
		$pwdHash = $this->hasher->hashPassword($user, $pwd);
		$this->repository->upgradePassword($user, $pwdHash);
        $io->success("Created {$email} {$pwd}");

        return Command::SUCCESS;
    }
}
