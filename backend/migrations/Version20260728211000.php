<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique view tracking by IP hash for news articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE news_article_views (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, ip_hash VARCHAR(64) NOT NULL, first_viewed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_viewed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', views_count INT DEFAULT 1 NOT NULL, INDEX IDX_NEWS_ARTICLE_VIEW_ARTICLE (article_id), UNIQUE INDEX UNIQ_NEWS_ARTICLE_VIEW_IP (article_id, ip_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE news_article_views ADD CONSTRAINT FK_NEWS_ARTICLE_VIEW_ARTICLE FOREIGN KEY (article_id) REFERENCES news_articles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_article_views DROP FOREIGN KEY FK_NEWS_ARTICLE_VIEW_ARTICLE');
        $this->addSql('DROP TABLE news_article_views');
    }
}
