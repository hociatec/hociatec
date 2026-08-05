<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Command;

use App\Module\Order\Infrastructure\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:invoices:secure-storage', description: 'Move invoice documents out of the public web directory.')]
final class SecureInvoiceStorageCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 500;

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Nombre de commandes à traiter par lot.', self::DEFAULT_BATCH_SIZE)
            ->addOption('after-id', null, InputOption::VALUE_REQUIRED, 'Reprendre après cet identifiant de commande.', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = max(1, min(1000, (int) $input->getOption('batch-size')));
        $lastId = max(0, (int) $input->getOption('after-id'));
        $sourceDirectory = $this->projectDir.'/public/uploads/invoices';
        $targetDirectory = $this->projectDir.'/var/private/invoices';
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            $output->writeln('<error>Impossible de créer le stockage privé des factures.</error>');

            return Command::FAILURE;
        }

        $migrated = 0;
        do {
            $orders = $this->orders->findWithInvoiceDocumentsAfterId($lastId, $batchSize);
            foreach ($orders as $order) {
                $orderId = $order->getId();
                if (!is_int($orderId)) {
                    continue;
                }
                $lastId = $orderId;

                foreach ([['pdf', $order->getInvoicePdfPath()], ['xml', $order->getInvoiceXmlPath()]] as [$extension, $path]) {
                    if (!is_string($path) || '' === $path) {
                        continue;
                    }
                    $filename = basename($path);
                    if (!preg_match('/^[A-Za-z0-9._-]+\\.'.preg_quote((string) $extension, '/').'$/i', $filename)) {
                        $output->writeln('<comment>Chemin ignoré pour la commande '.$orderId.'.</comment>');
                        continue;
                    }

                    $source = $sourceDirectory.'/'.$filename;
                    $target = $targetDirectory.'/'.$filename;
                    if (is_file($source) && !is_file($target) && !rename($source, $target)) {
                        $output->writeln('<error>Impossible de déplacer '.$filename.'. Reprise possible avec --after-id='.$lastId.'.</error>');

                        return Command::FAILURE;
                    }
                    if (is_file($target)) {
                        if ('pdf' === $extension) {
                            $order->setInvoicePdfPath('private/invoices/'.$filename);
                        } else {
                            $order->setInvoiceXmlPath('private/invoices/'.$filename);
                        }
                        ++$migrated;
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        } while (count($orders) === $batchSize);

        $output->writeln(sprintf('<info>%d document(s) sécurisé(s).</info>', $migrated));

        return Command::SUCCESS;
    }
}
