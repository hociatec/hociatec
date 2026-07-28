<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news articles and comments module with initial Hociatec article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE news_articles (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL, excerpt LONGTEXT NOT NULL, content LONGTEXT NOT NULL, category VARCHAR(120) DEFAULT NULL, is_published TINYINT(1) DEFAULT 1 NOT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_NEWS_ARTICLES_PUBLISHED (is_published, published_at), UNIQUE INDEX UNIQ_NEWS_ARTICLES_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE news_comments (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_NEWS_COMMENTS_ARTICLE (article_id, created_at), INDEX IDX_NEWS_COMMENTS_AUTHOR (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE news_comments ADD CONSTRAINT FK_NEWS_COMMENTS_ARTICLE FOREIGN KEY (article_id) REFERENCES news_articles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news_comments ADD CONSTRAINT FK_NEWS_COMMENTS_AUTHOR FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql(
            <<<'SQL'
INSERT INTO news_articles (title, slug, excerpt, content, category, is_published, published_at, created_at, updated_at)
VALUES (
  'Hociatec renforce son accompagnement numérique de proximité',
  'hociatec-renforce-son-accompagnement-numerique-de-proximite',
  'Hociatec structure son offre autour d’un accompagnement plus lisible, plus réactif et adapté aux besoins des particuliers comme des professionnels.',
  'Hociatec poursuit le développement de ses services numériques avec une priorité claire : proposer un accompagnement fiable, humain et accessible.\n\nNotre objectif est de simplifier les démarches informatiques du quotidien, depuis le choix d’un équipement jusqu’au suivi d’un besoin technique plus avancé. Cette évolution s’inscrit dans une logique de proximité : comprendre le contexte réel de chaque client, recommander une solution adaptée et assurer un suivi sérieux dans la durée.\n\nLes prochaines semaines permettront de consolider plusieurs axes : amélioration du catalogue de services, renforcement du suivi client, meilleure visibilité des actualités et intégration progressive d’outils facilitant les échanges avec l’équipe Hociatec.\n\nCette actualité marque une étape importante : Hociatec veut construire une expérience plus claire, plus professionnelle et plus utile pour chaque utilisateur.',
  'Vie de l’entreprise',
  1,
  NOW(),
  NOW(),
  NOW()
)
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_comments DROP FOREIGN KEY FK_NEWS_COMMENTS_ARTICLE');
        $this->addSql('ALTER TABLE news_comments DROP FOREIGN KEY FK_NEWS_COMMENTS_AUTHOR');
        $this->addSql('DROP TABLE news_comments');
        $this->addSql('DROP TABLE news_articles');
    }
}
