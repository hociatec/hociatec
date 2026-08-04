<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:auth:purge-refresh-tokens', description: 'Supprime les refresh tokens expirés ou révoqués.')]
final class PurgeRefreshTokensCommand extends Command
{
    public function __construct(private readonly RefreshTokenRepository $refreshTokens)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('revoked-retention-days', null, InputOption::VALUE_REQUIRED, 'Durée de conservation des tokens révoqués.', '7');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionDays = max(0, (int) $input->getOption('revoked-retention-days'));
        $now = new \DateTimeImmutable();
        $revokedBefore = $now->sub(new \DateInterval('P'.$retentionDays.'D'));

        $deleted = $this->refreshTokens->purgeExpiredOrRevokedBefore($now, $revokedBefore);
        $io->success(sprintf('%d refresh token(s) supprimé(s).', $deleted));

        return Command::SUCCESS;
    }
}
