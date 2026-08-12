<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

use App\Module\Auth\Infrastructure\Seed\E2eDataSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:e2e:seed', description: 'Seed stable end-to-end users and orders for Playwright runs.')]
final class SeedE2eDataCommand extends Command
{
    public function __construct(private readonly E2eDataSeeder $seeder)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!E2eCommandGuard::isAllowed()) {
            $output->writeln(sprintf('<error>%s</error>', E2eCommandGuard::denialMessage('app:e2e:seed')));

            return Command::FAILURE;
        }

        $this->seeder->seed();

        $output->writeln('<info>E2E users and orders seeded.</info>');

        return Command::SUCCESS;
    }
}
