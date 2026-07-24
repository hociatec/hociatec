<?php

declare(strict_types=1);

namespace App\Module\Cart\Command;

use App\Module\Cart\Entity\CartSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:carts:cleanup', description: 'Delete expired cart sessions by updatedAt threshold')]
final class CleanExpiredCartsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Days of inactivity before deletion', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = max(1, (int) ($input->getOption('days') ?? 30));
        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d days', $days));

        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(CartSession::class, 'c')
            ->andWhere('c.updatedAt < :threshold')
            ->setParameter('threshold', $threshold);

        $expired = $qb->getQuery()->getResult();

        foreach ($expired as $cart) {
            $this->em->remove($cart);
        }

        $this->em->flush();
        $output->writeln(sprintf('Deleted %d expired carts (>%d days).', \count($expired), $days));

        return Command::SUCCESS;
    }
}
