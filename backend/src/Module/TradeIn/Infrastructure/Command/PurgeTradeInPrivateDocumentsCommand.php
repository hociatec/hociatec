<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Command;

use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use App\Shared\Application\UnitOfWork;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:trade-in:purge-private-documents', description: 'Supprime les RIB et justificatifs trade-in au-delà de la durée de rétention.')]
final class PurgeTradeInPrivateDocumentsCommand extends Command
{
    public function __construct(
        private readonly TradeInRequestRepository $requests,
        private readonly TradeInPrivateFileStorage $files,
        private readonly UnitOfWork $persistence,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('retention-days', null, InputOption::VALUE_REQUIRED, 'Durée de conservation après clôture.', '180')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre maximum de dossiers à purger par exécution.', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionDays = max(1, (int) $input->getOption('retention-days'));
        $limit = max(1, (int) $input->getOption('limit'));
        $closedBefore = (new \DateTimeImmutable())->sub(new \DateInterval('P'.$retentionDays.'D'));

        $requests = $this->requests->findClosedWithExpiredPrivateDocuments($closedBefore, $limit);
        $purged = 0;

        foreach ($requests as $request) {
            $ribPath = $request->getRibPath();
            $receiptPath = $request->getReceiptPath();
            if (is_string($ribPath) && '' !== $ribPath) {
                $this->files->delete($ribPath);
            }
            if (is_string($receiptPath) && '' !== $receiptPath) {
                $this->files->delete($receiptPath);
            }

            $request->clearPrivateDocuments();
            $this->persistence->persist($request);
            ++$purged;
        }

        if ($purged > 0) {
            $this->persistence->flush();
        }

        $io->success(sprintf('%d demande(s) de reprise purgée(s).', $purged));

        return Command::SUCCESS;
    }
}
