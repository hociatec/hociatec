<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Shared\Application\UnitOfWork;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:auth:revoke-user-refresh-tokens', description: 'Révoque toutes les sessions refresh actives d’un utilisateur.')]
final class RevokeUserRefreshTokensCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly RefreshTokenRevocationService $revocations,
        private readonly UnitOfWork $unitOfWork,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de l’utilisateur.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) $input->getArgument('email'));
        if ('' === $email) {
            $io->error('Une adresse e-mail valide est requise.');

            return Command::INVALID;
        }

        $user = $this->users->findOneByEmailInsensitive($email);
        if (null === $user) {
            $io->error('Utilisateur introuvable.');

            return Command::FAILURE;
        }

        $this->revocations->revokeAllForUser($user);
        $this->unitOfWork->flush();

        $io->success(sprintf('Toutes les sessions refresh actives de %s ont été révoquées.', $user->getEmail()));

        return Command::SUCCESS;
    }
}
