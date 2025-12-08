<?php

declare(strict_types=1);

namespace App\DataFixtures\Catalog;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    private const PRODUCT_REFERENCE_PREFIX = 'catalog_product_';
    private const FEATURED_HOME_COUNT = 6;

    /**
     * @var array<string, list<array{name: string, short: string, description: string, price: int, stock: int, image: string, imageSize: int}>>
     */
    private const PRODUCT_BLUEPRINTS = [
        'pc-portables' => [
            [
                'name' => 'Ultrabook Orion 14',
                'short' => 'Ultrabook 14 pouces fin et autonome.',
                'description' => 'Ultrabook Orion 14 associe chassis aluminium, ecran IPS 14 pouces et autonomie de 12h pour les deplacements.',
                'price' => 119900,
                'stock' => 26,
                'sellingType' => 'rental',
                'image' => 'ultrabook-orion-14.jpg',
                'imageSize' => 285000,
            ],
            [
                'name' => 'Laptop Proxima 16',
                'short' => 'Portable 16 pouces pour la creation.',
                'description' => 'Laptop Proxima 16 integre CPU Intel serie H, GPU RTX et dalle 16 pouces QHD pour creation multimedia.',
                'price' => 159900,
                'stock' => 19,
                'image' => 'laptop-proxima-16.jpg',
                'imageSize' => 301200,
            ],
            [
                'name' => 'Notebook Vega Air 13',
                'short' => '13 pouces leger et silencieux.',
                'description' => 'Notebook Vega Air 13 pese moins de 1 kg, adopte stockage NVMe et refroidissement passif pour un usage mobile.',
                'price' => 98900,
                'stock' => 34,
                'image' => 'notebook-vega-air-13.jpg',
                'imageSize' => 257800,
            ],
            [
                'name' => 'Gaming Laptop Nova X15',
                'short' => 'Laptop gaming 15 pouces 240 Hz.',
                'description' => 'Gaming Laptop Nova X15 embarque GPU RTX 4070, ecran 15 pouces 240 Hz et clavier mecanique optique.',
                'price' => 189900,
                'stock' => 21,
                'image' => 'gaming-laptop-nova-x15.jpg',
                'imageSize' => 318400,
            ],
            [
                'name' => 'Chromebook Flexia 12',
                'short' => 'Chromebook convertible tactile.',
                'description' => 'Chromebook Flexia 12 pivote a 360 degres, dispose d un stylet USI et assure 11h d autonomie.',
                'price' => 64900,
                'stock' => 28,
                'image' => 'chromebook-flexia-12.jpg',
                'imageSize' => 242600,
            ],
            [
                'name' => 'Laptop Titan Creator 17',
                'short' => '17 pouces pour le montage video.',
                'description' => 'Laptop Titan Creator 17 combine Core i9, 64 Go de RAM, double SSD NVMe et dalle mini LED HDR.',
                'price' => 229900,
                'stock' => 14,
                'sellingType' => 'rental',
                'image' => 'laptop-titan-creator-17.jpg',
                'imageSize' => 330100,
            ],
            [
                'name' => 'Workstation Mobile Argo',
                'short' => 'Mobile workstation certifiee ISV.',
                'description' => 'Workstation Mobile Argo integre GPU RTX A2000, ecran 15 pouces UHD et module 4G pour les pros nomades.',
                'price' => 249900,
                'stock' => 11,
                'image' => 'workstation-mobile-argo.jpg',
                'imageSize' => 347900,
            ],
            [
                'name' => 'Laptop Pixel Edge 14',
                'short' => 'Ultraportable OLED 90 Hz.',
                'description' => 'Laptop Pixel Edge 14 propose ecran OLED 90 Hz, Wi-Fi 7 et audio Dolby Atmos dans 1.1 kg.',
                'price' => 114900,
                'stock' => 30,
                'image' => 'laptop-pixel-edge-14.jpg',
                'imageSize' => 276500,
            ],
        ],
        'pc-bureau' => [
            [
                'name' => 'Desktop Atlas Pro',
                'short' => 'Tour polyvalente Intel Core i7.',
                'description' => 'Desktop Atlas Pro offre Core i7 14700, 32 Go de RAM DDR5 et SSD NVMe 1 To pour bureautique avancee.',
                'price' => 134900,
                'stock' => 22,
                'sellingType' => 'rental',
                'image' => 'desktop-atlas-pro.jpg',
                'imageSize' => 289400,
            ],
            [
                'name' => 'Mini PC Nucleus One',
                'short' => 'Mini PC silencieux sur VESA.',
                'description' => 'Mini PC Nucleus One tient dans la main, integre Ryzen 7 7840U, supporte Quad Display et fixation VESA.',
                'price' => 89900,
                'stock' => 37,
                'image' => 'mini-pc-nucleus-one.jpg',
                'imageSize' => 210300,
            ],
            [
                'name' => 'Workstation Boreal X',
                'short' => 'Station de travail double GPU.',
                'description' => 'Workstation Boreal X accueille double RTX 6000, processeur Threadripper Pro et stockage RAID NVMe.',
                'price' => 429900,
                'stock' => 6,
                'image' => 'workstation-boreal-x.jpg',
                'imageSize' => 412800,
            ],
            [
                'name' => 'Desktop Gaming Helios',
                'short' => 'Tour gaming RGB 240 mm AIO.',
                'description' => 'Desktop Gaming Helios combine Ryzen 7 7800X3D, RTX 4080 Super et refroidissement liquide 240 mm.',
                'price' => 279900,
                'stock' => 13,
                'image' => 'desktop-gaming-helios.jpg',
                'imageSize' => 365000,
            ],
            [
                'name' => 'Desktop Starter Neo',
                'short' => 'PC de bureau entree de gamme evolutif.',
                'description' => 'Desktop Starter Neo repose sur Ryzen 5 5600G, SSD 512 Go et boitier micro ATX compact.',
                'price' => 69900,
                'stock' => 41,
                'sellingType' => 'rental',
                'image' => 'desktop-starter-neo.jpg',
                'imageSize' => 243100,
            ],
            [
                'name' => 'All in One Vision 24',
                'short' => 'PC tout en un 24 pouces tactile.',
                'description' => 'All in One Vision 24 combine dalle tactile FHD, webcam 5 MP et charge sans fil integree au pied.',
                'price' => 124900,
                'stock' => 18,
                'image' => 'all-in-one-vision-24.jpg',
                'imageSize' => 298600,
            ],
            [
                'name' => 'Server Edge Micro',
                'short' => 'Serveur edge 10 Gb compact.',
                'description' => 'Server Edge Micro fournit Xeon D, 6 baies NVMe hot swap et double 10 GbE pour sites distants.',
                'price' => 359900,
                'stock' => 9,
                'image' => 'server-edge-micro.jpg',
                'imageSize' => 382400,
            ],
            [
                'name' => 'Desktop Creator Flux',
                'short' => 'Tour creation Adobe et DaVinci.',
                'description' => 'Desktop Creator Flux livre RTX 4070 Ti Super, 64 Go DDR5 et stockage hybride SSD plus HDD NAS.',
                'price' => 244900,
                'stock' => 15,
                'image' => 'desktop-creator-flux.jpg',
                'imageSize' => 336700,
            ],
        ],
        'smartphones' => [
            [
                'name' => 'Smartphone Zenith S5',
                'short' => 'Ecran AMOLED 120 Hz et 5G.',
                'description' => 'Smartphone Zenith S5 propose SoC Snapdragon 8 Gen 2, ecran 6.7 pouces 120 Hz et triple capteur 64 MP.',
                'price' => 89900,
                'stock' => 40,
                'image' => 'smartphone-zenith-s5.jpg',
                'imageSize' => 198700,
            ],
            [
                'name' => 'Smartphone Atlas Fold',
                'short' => 'Format pliable double ecran.',
                'description' => 'Smartphone Atlas Fold offre ecran interne 7.6 pouces, charniere renforcee et stylet Bluetooth.',
                'price' => 189900,
                'stock' => 17,
                'sellingType' => 'rental',
                'image' => 'smartphone-atlas-fold.jpg',
                'imageSize' => 265400,
            ],
            [
                'name' => 'Smartphone Nova Lite',
                'short' => 'Milieu de gamme photo 108 MP.',
                'description' => 'Smartphone Nova Lite combine capteur 108 MP, batterie 5000 mAh et charge 67 W.',
                'price' => 36900,
                'stock' => 52,
                'image' => 'smartphone-nova-lite.jpg',
                'imageSize' => 176800,
            ],
            [
                'name' => 'Smartphone Titan Rugged',
                'short' => 'Smartphone durci IP69K.',
                'description' => 'Smartphone Titan Rugged certifie IP69K, 5G SA, batterie 7000 mAh et cam thermique FLIR.',
                'price' => 59900,
                'stock' => 33,
                'image' => 'smartphone-titan-rugged.jpg',
                'imageSize' => 204500,
            ],
            [
                'name' => 'Smartphone Pulse 5C',
                'short' => 'Compact 5.4 pouces premium.',
                'description' => 'Smartphone Pulse 5C mise sur format compact, cadre alu et double capteur stabilise.',
                'price' => 79900,
                'stock' => 27,
                'image' => 'smartphone-pulse-5c.jpg',
                'imageSize' => 190400,
            ],
            [
                'name' => 'Smartphone Edge Photon',
                'short' => 'Flagship camera periscope.',
                'description' => 'Smartphone Edge Photon integre zoom optique 5x, Snapdragon 8s Gen 3 et charge 120 W.',
                'price' => 129900,
                'stock' => 23,
                'image' => 'smartphone-edge-photon.jpg',
                'imageSize' => 222600,
            ],
            [
                'name' => 'Smartphone Wave SE',
                'short' => 'Entree de gamme 5G polyvalente.',
                'description' => 'Smartphone Wave SE fournit 5G, ecran 6.6 pouces 90 Hz et batterie 5000 mAh sous 200 euros.',
                'price' => 21900,
                'stock' => 61,
                'image' => 'smartphone-wave-se.jpg',
                'imageSize' => 168900,
            ],
            [
                'name' => 'Smartphone Horizon Max',
                'short' => 'Smartphone multimedia 1 To.',
                'description' => 'Smartphone Horizon Max affiche 1 To de stockage UFS 4.0, DAC Hi-Res et hauts parleurs stereo.',
                'price' => 149900,
                'stock' => 12,
                'image' => 'smartphone-horizon-max.jpg',
                'imageSize' => 248300,
            ],
        ],
        'tablettes' => [
            [
                'name' => 'Tablette Slate 11 Pro',
                'short' => '11 pouces 120 Hz avec stylet.',
                'description' => 'Tablette Slate 11 Pro livre dalle 11 pouces 120 Hz, stylet actif et interface desktop mode.',
                'price' => 64900,
                'stock' => 36,
                'image' => 'tablette-slate-11-pro.jpg',
                'imageSize' => 205600,
            ],
            [
                'name' => 'Tablette Pixelboard 13',
                'short' => 'Tablette graphique 13 pouces.',
                'description' => 'Tablette Pixelboard 13 destinee aux creatifs avec stylet 8192 niveaux et support inclinaison.',
                'price' => 49900,
                'stock' => 24,
                'image' => 'tablette-pixelboard-13.jpg',
                'imageSize' => 198200,
            ],
            [
                'name' => 'Tablette Hybridia X2',
                'short' => '2 en 1 detachable avec 5G.',
                'description' => 'Tablette Hybridia X2 combine clavier detachable retroeclaire, modem 5G et autonomie 14h.',
                'price' => 89900,
                'stock' => 18,
                'sellingType' => 'rental',
                'image' => 'tablette-hybridia-x2.jpg',
                'imageSize' => 234900,
            ],
            [
                'name' => 'Tablette Edu Junior 10',
                'short' => 'Tablette educative renforcee.',
                'description' => 'Tablette Edu Junior 10 livre coque antichoc, controle parental Web et suite pedagogique.',
                'price' => 24900,
                'stock' => 44,
                'image' => 'tablette-edu-junior-10.jpg',
                'imageSize' => 176400,
            ],
            [
                'name' => 'Tablette Studio View 16',
                'short' => 'Tablette de retouche HDR.',
                'description' => 'Tablette Studio View 16 propose ecran 16 pouces HDR 600, 16 Go RAM et 1 To NVMe.',
                'price' => 119900,
                'stock' => 9,
                'image' => 'tablette-studio-view-16.jpg',
                'imageSize' => 265900,
            ],
            [
                'name' => 'Tablette Compact One 8',
                'short' => 'Format 8 pouces leger.',
                'description' => 'Tablette Compact One 8 pese 310 g, ecran 8 pouces FHD et batterie 6000 mAh.',
                'price' => 18900,
                'stock' => 57,
                'image' => 'tablette-compact-one-8.jpg',
                'imageSize' => 152700,
            ],
            [
                'name' => 'Tablette Business Dock 12',
                'short' => 'Tablette professionnelle avec hub.',
                'description' => 'Tablette Business Dock 12 inclut hub USB-C, lecteur smartcard et chiffrement TPM.',
                'price' => 79900,
                'stock' => 16,
                'image' => 'tablette-business-dock-12.jpg',
                'imageSize' => 223500,
            ],
            [
                'name' => 'Tablette Media Max 10',
                'short' => 'Tablette multimedia Dolby Atmos.',
                'description' => 'Tablette Media Max 10 livre quatre haut-parleurs Dolby Atmos, ecran 10 pouces et double micro.',
                'price' => 32900,
                'stock' => 49,
                'image' => 'tablette-media-max-10.jpg',
                'imageSize' => 184600,
            ],
        ],
        'peripheriques' => [
            [
                'name' => 'Moniteur Polaris 27 IPS',
                'short' => '27 pouces QHD 165 Hz.',
                'description' => 'Moniteur Polaris 27 IPS affiche QHD 165 Hz, couverture 98 pourcent DCI-P3 et compatibilite G-Sync.',
                'price' => 39900,
                'stock' => 32,
                'image' => 'moniteur-polaris-27-ips.jpg',
                'imageSize' => 265400,
            ],
            [
                'name' => 'Clavier Mechanic K90',
                'short' => 'Clavier mecanique switchs lineaires.',
                'description' => 'Clavier Mechanic K90 adopte switchs lineaires lubrifies, chassis alu et triple connectique.',
                'price' => 14900,
                'stock' => 58,
                'image' => 'clavier-mechanic-k90.jpg',
                'imageSize' => 142300,
            ],
            [
                'name' => 'Souris Vector Pro',
                'short' => 'Souris sans fil 26K DPI.',
                'description' => 'Souris Vector Pro pese 69 g, capteur 26K DPI, autonomie 120h et kit grip magnetique.',
                'price' => 9900,
                'stock' => 63,
                'image' => 'souris-vector-pro.jpg',
                'imageSize' => 118900,
            ],
            [
                'name' => 'Casque Audio Nova 7X',
                'short' => 'Casque gaming sans fil 7.1.',
                'description' => 'Casque Audio Nova 7X propose surround 7.1 low latency, micro a reduction active et double connexion.',
                'price' => 17900,
                'stock' => 47,
                'image' => 'casque-audio-nova-7x.jpg',
                'imageSize' => 198600,
            ],
            [
                'name' => 'Routeur Mesh Aero AXE',
                'short' => 'Wi-Fi 6E tri bande.',
                'description' => 'Routeur Mesh Aero AXE couvre 600 m2, Wi-Fi 6E tri bande et securite WPA3 Enterprise.',
                'price' => 34900,
                'stock' => 26,
                'image' => 'routeur-mesh-aero-axe.jpg',
                'imageSize' => 201200,
            ],
            [
                'name' => 'Webcam Streamline 4K',
                'short' => 'Webcam 4K HDR avec IA.',
                'description' => 'Webcam Streamline 4K capture 4K HDR, cadrage automatique IA et micro double beamforming.',
                'price' => 12900,
                'stock' => 52,
                'image' => 'webcam-streamline-4k.jpg',
                'imageSize' => 164400,
            ],
            [
                'name' => 'Station Dock Thunderbolt',
                'short' => 'Dock TB4 12 ports.',
                'description' => 'Station Dock Thunderbolt ajoute 12 ports, triple ecran 4K et charge 90 W pour laptop.',
                'price' => 29900,
                'stock' => 29,
                'image' => 'station-dock-thunderbolt.jpg',
                'imageSize' => 212500,
            ],
            [
                'name' => 'Microphone Podcast Wave',
                'short' => 'Micro USB XLR hybride.',
                'description' => 'Microphone Podcast Wave combine sortie XLR, USB-C 24 bits et mixeur logiciel multi pistes.',
                'price' => 18900,
                'stock' => 39,
                'image' => 'microphone-podcast-wave.jpg',
                'imageSize' => 176900,
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $categoryCodes = [
            'pc-portables' => 'NBK',
            'pc-bureau' => 'DES',
            'smartphones' => 'PHN',
            'tablettes' => 'TAB',
            'peripheriques' => 'PER',
        ];

        $globalIndex = 1;
        foreach (self::PRODUCT_BLUEPRINTS as $categorySlug => $products) {
            /** @var Category $category */
            $category = $this->getReference(CategoryFixtures::REFERENCE_PREFIX . $categorySlug, Category::class);

            foreach ($products as $data) {
                $slug = $this->slugify($data['name']);
                $sku = sprintf('%s%03d', $categoryCodes[$categorySlug], $globalIndex);

                $product = new Product(
                    $data['name'],
                    $slug,
                    $sku,
                    $data['description'],
                    $data['price'],
                    $data['stock'],
                    $category,
                );

                $sellingType = $data['sellingType'] ?? 'sale';
                $product
                    ->setShortDescription($data['short'])
                    ->setImageName($data['image'])
                    ->setImageSize($this->resolveImageSize($data['image'], $data['imageSize']))
                    ->setImageAlt($data['name'])
                    ->setIsFeaturedHome($globalIndex <= self::FEATURED_HOME_COUNT)
                    ->setSellingType($sellingType);

                $manager->persist($product);
                $this->addReference(self::getReferenceName($globalIndex), $product);

                ++$globalIndex;
            }
        }

        $manager->flush();
    }

    private function slugify(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }

        $value = preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '';

        return strtolower(trim($value, '-'));
    }

    public static function getReferenceName(int $index): string
    {
        return self::PRODUCT_REFERENCE_PREFIX . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    private function resolveImageSize(string $filename, ?int $fallback = null): ?int
    {
        $projectDir = dirname(__DIR__, 3);
        $path = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) {
            $size = filesize($path);

            if ($size !== false) {
                return (int) $size;
            }
        }

        return $fallback;
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
