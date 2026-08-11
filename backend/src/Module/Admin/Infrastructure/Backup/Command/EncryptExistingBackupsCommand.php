<?php

declare(strict_types=1);

namespace App\Module\Admin\Infrastructure\Backup\Command;

use App\Module\Admin\Application\Backup\Storage\BackupFileStorage;
use App\Module\Admin\Application\Backup\Workflow\BackupEncryptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:backups:encrypt-existing', description: 'Encrypt existing unencrypted database backups.')]
final class EncryptExistingBackupsCommand extends Command
{
    public function __construct(
        private readonly BackupFileStorage $files,
        private readonly BackupEncryptionService $encryption,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $count = 0;
            foreach ($this->files->legacyPaths() as $sourcePath) {
                $targetPath = $sourcePath.'.enc';
                if (!is_file($targetPath)) {
                    $this->encryption->encryptFile($sourcePath, $targetPath);
                }
                if (is_file($targetPath) && unlink($sourcePath)) {
                    ++$count;
                }
            }

            $output->writeln(sprintf('<info>%d ancienne(s) sauvegarde(s) chiffrée(s).</info>', $count));

            return Command::SUCCESS;
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
