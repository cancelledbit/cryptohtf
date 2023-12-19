<?php

namespace App\Command;

use App\Service\Vault\Event\VaultUpdatedEvent;
use App\Service\Vault\Event\VaultLockedEvent;
use App\Service\Vault\Event\VaultRemovedEvent;
use App\Service\Vault\Event\VaultUnlockedEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:test',
    description: 'Add a short description for your command',
)]
class TestCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }
    private const RECIPIENT_MAP = [
        VaultUpdatedEvent::class => ['409842850',],
        VaultRemovedEvent::class => ['409842850',],
        VaultLockedEvent::class => ['409842850',],
        VaultUnlockedEvent::class => ['409842850',],
    ];
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
		echo json_encode(self::RECIPIENT_MAP);
        return Command::SUCCESS;
    }
}
