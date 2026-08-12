<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

use App\Module\Auth\Infrastructure\Seed\E2eDataPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:e2e:purge', description: 'Purge seeded and Playwright-generated end-to-end data.')]
final class PurgeE2eDataCommand extends Command
{
    public function __construct(private readonly E2eDataPurger $purger)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!E2eCommandGuard::isAllowed()) {
            $output->writeln(sprintf('<error>%s</error>', E2eCommandGuard::denialMessage('app:e2e:purge')));

            return Command::FAILURE;
        }

        $deletedRows = $this->purger->purge();

        $output->writeln(sprintf('<info>E2E data purged (%d deleted rows).</info>', $deletedRows));

        return Command::SUCCESS;
    }
}
