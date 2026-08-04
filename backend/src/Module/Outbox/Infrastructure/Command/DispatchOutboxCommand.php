<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Command;

use App\Module\Outbox\Application\OutboxDispatcher;
use App\Module\Outbox\Application\OutboxEventStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:outbox:dispatch', description: 'Dispatch pending outbox events.')]
final class DispatchOutboxCommand extends Command
{
    public function __construct(private readonly OutboxDispatcher $dispatcher, private readonly OutboxEventStore $events)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of events to dispatch.', '50');
        $this->addOption('purge-finalized-older-than', null, InputOption::VALUE_REQUIRED, 'Purge processed/dead events older than this relative date.', '7 days');
        $this->addOption('stale-processing-after', null, InputOption::VALUE_REQUIRED, 'Report processing events older than this relative duration.', '15 minutes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processed = $this->dispatcher->dispatchDue((int) $input->getOption('limit'));
        $threshold = new \DateTimeImmutable('-'.(string) $input->getOption('purge-finalized-older-than'));
        $purged = $this->events->purgeFinalizedBefore($threshold);
        $metrics = $this->events->metricsSnapshot(new \DateTimeImmutable('-'.(string) $input->getOption('stale-processing-after')));
        $output->writeln(sprintf('Dispatched %d outbox event(s).', $processed));
        $output->writeln(sprintf('Purged %d finalized outbox event(s).', $purged));
        $output->writeln(sprintf(
            'Outbox metrics: pending=%d oldest_pending_age_seconds=%s failed=%d stale_processing=%d dead=%d',
            $metrics->pendingEvents,
            null === $metrics->oldestPendingAgeSeconds ? 'null' : (string) $metrics->oldestPendingAgeSeconds,
            $metrics->failedEvents,
            $metrics->staleProcessingEvents,
            $metrics->deadEvents,
        ));

        if ($metrics->staleProcessingEvents > 0) {
            $output->writeln(sprintf('<error>%d outbox event(s) are stuck in processing.</error>', $metrics->staleProcessingEvents));
        }

        return Command::SUCCESS;
    }
}
