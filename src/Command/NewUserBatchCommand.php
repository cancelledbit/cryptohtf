<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:new-user-batch',
    description: 'Add a short description for your command',
)]
class NewUserBatchCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csv', InputArgument::REQUIRED, 'inline "name;lastname;email|"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csv = $input->getArgument('csv');
        foreach (explode('|', $csv) as $user) {
            $userParts = explode(';', $user);
            if (3 !== count($userParts)) {
                $io->error("invalid string {$user}");
                continue;
            }
            $args = new ArrayInput([
                'command' => 'app:new-user',
                'first_name' => $userParts[0],
                'last_name' => $userParts[0],
                'email' => $userParts[0],
            ]);
            $this->getApplication()->doRun($args, $output);
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
