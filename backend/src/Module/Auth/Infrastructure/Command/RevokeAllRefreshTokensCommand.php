<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Shared\Application\UnitOfWork;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:auth:revoke-all-refresh-tokens', description: 'Révoque toutes les sessions refresh actives de tous les utilisateurs.')]
final class RevokeAllRefreshTokensCommand extends Command
{
    public function __construct(
        private readonly RefreshTokenRevocationService $revocations,
        private readonly UnitOfWork $unitOfWork,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('confirm', null, InputOption::VALUE_NONE, 'Confirme la révocation globale.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$input->getOption('confirm')) {
            $io->error('Ajouter --confirm pour révoquer globalement toutes les sessions refresh actives.');

            return Command::INVALID;
        }

        $count = $this->revocations->revokeAllActive();
        $this->unitOfWork->flush();

        $io->success(sprintf('%d session(s) refresh active(s) ont été révoquées globalement.', $count));

        return Command::SUCCESS;
    }
}
