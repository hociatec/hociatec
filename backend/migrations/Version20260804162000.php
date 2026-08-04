<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renseigne une illustration externe pour chaque service du catalogue.';
    }

    public function up(Schema $schema): void
    {
        $illustrations = [
            'Développement d’application web métier' => ['/service-illustrations/application-metier.svg', 'Illustration de développement d application web métier'],
            'Refonte de site internet' => ['/service-illustrations/refonte-site.svg', 'Illustration de refonte de site internet'],
            'Audit d’accessibilité numérique' => ['/service-illustrations/audit-accessibilite.svg', 'Illustration d audit d accessibilité numérique'],
            'Audit technique web' => ['/service-illustrations/audit-web.svg', 'Illustration d audit technique web'],
            'Audit de performance web' => ['/service-illustrations/audit-web.svg', 'Illustration d audit de performance web'],
            'Audit SEO technique' => ['/service-illustrations/audit-web.svg', 'Illustration d audit SEO technique'],
            'Audit UX et parcours utilisateur' => ['/service-illustrations/audit-ux.svg', 'Illustration d audit UX et parcours utilisateur'],
            'Assistance informatique à distance' => ['/service-illustrations/assistance-distance.svg', 'Illustration d assistance informatique à distance'],
            'Installation réseau et NAS' => ['/service-illustrations/reseau-nas.svg', 'Illustration d installation réseau et NAS'],
            'Formation cybersécurité et bonnes pratiques' => ['/service-illustrations/formation-cybersecurite.svg', 'Illustration de formation cybersécurité et bonnes pratiques'],
            'Reconditionnement et remise en service de matériel' => ['/service-illustrations/reconditionnement.svg', 'Illustration de reconditionnement et remise en service de matériel'],
        ];

        foreach ($illustrations as $title => [$imageUrl, $imageAlt]) {
            $this->addSql(
                'UPDATE quote_services SET image_external_url = :image_url, image_alt = COALESCE(NULLIF(TRIM(image_alt), \'\'), :image_alt) WHERE title = :title AND (image_external_url IS NULL OR TRIM(image_external_url) = \'\')',
                ['image_url' => $imageUrl, 'image_alt' => $imageAlt, 'title' => $title],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE quote_services SET image_external_url = NULL WHERE image_external_url IN (
            '/service-illustrations/application-metier.svg',
            '/service-illustrations/refonte-site.svg',
            '/service-illustrations/audit-accessibilite.svg',
            '/service-illustrations/audit-web.svg',
            '/service-illustrations/audit-ux.svg',
            '/service-illustrations/assistance-distance.svg',
            '/service-illustrations/reseau-nas.svg',
            '/service-illustrations/formation-cybersecurite.svg',
            '/service-illustrations/reconditionnement.svg'
        )");
    }
}
