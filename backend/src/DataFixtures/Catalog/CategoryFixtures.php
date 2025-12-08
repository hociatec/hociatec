<?php

declare(strict_types=1);

namespace App\DataFixtures\Catalog;

use App\Module\Catalog\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const REFERENCE_PREFIX = 'catalog_category_';

    /**
     * @var array<string, array{name: string, description: string}>
     */
    private const CATEGORIES = [
        'pc-portables' => [
            'name' => 'PC portables',
            'description' => 'Ordinateurs portables pour les usages pro, gaming et multimedia.',
        ],
        'pc-bureau' => [
            'name' => 'PC de bureau',
            'description' => 'Tours evolutives et mini PC adaptes aux besoins intensifs.',
        ],
        'smartphones' => [
            'name' => 'Smartphones',
            'description' => 'Selection de telephones 5G, photophones et modeles endurcis.',
        ],
        'tablettes' => [
            'name' => 'Tablettes',
            'description' => 'Tablettes hybrides, graphiques et professionnelles.',
        ],
        'peripheriques' => [
            'name' => 'Peripheriques et accessoires',
            'description' => 'Ecrans, claviers, reseau et accessoires pour optimiser votre setup.',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $slug => $data) {
            $category = new Category($data['name'], $slug);
            $category->setDescription($data['description']);
            $manager->persist($category);

            $this->addReference(self::REFERENCE_PREFIX . $slug, $category);
        }

        $manager->flush();
    }

    /**
     * @return list<string>
     */
    public static function getReferenceKeys(): array
    {
        return array_map(
            static fn (string $slug): string => self::REFERENCE_PREFIX . $slug,
            array_keys(self::CATEGORIES),
        );
    }
}
