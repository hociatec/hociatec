<?php

declare(strict_types=1);

namespace App\Shared\Outbox\Command;

use App\Shared\Outbox\OutboxDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:outbox:dispatch', description: 'Dispatch pending outbox events.')]
final class DispatchOutboxCommand extends Command
{
    public function __construct(private readonly OutboxDispatcher $dispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of events to dispatch.', '50');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processed = $this->dispatcher->dispatchDue((int) $input->getOption('limit'));
        $output->writeln(sprintf('Dispatched %d outbox event(s).', $processed));

        return Command::SUCCESS;
    }
}
