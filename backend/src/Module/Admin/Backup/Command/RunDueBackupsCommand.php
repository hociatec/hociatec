<?php

declare(strict_types=1);

namespace App\Module\Admin\Backup\Command;

use App\Module\Admin\Backup\Service\BackupManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:backups:run-due', description: 'Run the configured database backup when it is due')]
final class RunDueBackupsCommand extends Command
{
    public function __construct(private readonly BackupManager $backupManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Run a backup immediately, even when no scheduled backup is due.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $input->getOption('force')
                ? $this->backupManager->runBackup('manual')
                : $this->backupManager->runDue();
            if ($result === null) {
                $output->writeln('No backup due.');

                return Command::SUCCESS;
            }

            $output->writeln('Backup completed.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
