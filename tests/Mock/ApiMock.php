<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiMock extends MockHttpClient
{
    private string $baseUri = 'https://store.steampowered.com/api';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url): MockResponse
    {
        if ('GET' === $method && str_starts_with($url, $this->baseUri . '/appdetails?appids=')) {
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query, $params);

            if (isset($params['appids'])) {
                $id = $params['appids'];

                return $this->getAppDetailsMock($id);
            }

            throw new \UnexpectedValueException("Missing appids parameter in URL: $url");
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
    }

    private function generateMockResponse(mixed $data): MockResponse
    {
        return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), [
            'http_code' => Response::HTTP_OK,
            'response_headers' => [
                'content-type' => 'application/json',
            ],
        ]);
    }

    private function getAppDetailsMock(string $id): mixed
    {
        $data = [];
        // hollow knight
        $data['1'] = [
            'success' => true,
            'data' => [
                'type' => 'game',
                'name' => 'Hollow Knight: Silksong',
                'steam_appid' => 1030300,
                'required_age' => 0,
                'is_free' => false,
                'controller_support' => 'full',
                'detailed_description' => '<h2 class="bb_tag">Become the Princess Knight</h2>Play as Hornet, princess-protector of Hallownest, and adventure through a whole new kingdom ruled by silk and song! Captured and brought to this unfamiliar world, Hornet must battle foes and solve mysteries as she ascends on a deadly pilgrimage to the kingdom’s peak.<br><br><br>Hollow Knight: Silksong is the epic sequel to Hollow Knight, the award winning action-adventure. As the lethal hunter Hornet, journey to all-new lands, discover new powers, battle vast hordes of bugs and beasts and uncover ancient secrets tied to your nature and your past.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0010_3-spage.png?t=1695443850" /><h2 class="bb_tag">Game Features</h2><ul class="bb_ul"><li>Discover a whole new kingdom! Explore coral forests, mossy grottos, gilded cities and misted moors as you ascend to the shining citadel at the top of the world.<br></li><li>Engage in lethal acrobatic action! Wield a whole new suite of deadly moves as you dance between foes in deadly, beautiful combat.<br></li><li>Craft powerful tools! Master an ever-expanding arsenal of weapons, traps, and mechanisms to confound your enemies and explore new heights.<br></li><li>Solve shocking quests! Hunt down rare beasts, solve ancient mysteries and search for lost treasures to fulfil the wishes of the downtrodden and restore the kingdom’s hope. Prepare for the unexpected!<br></li><li>Face over 150 all-new foes! Beasts and hunters, assassins and kings, monsters and knights, defeat them all with bravery and skill!<br></li><li>Challenge Silk Soul mode! Once you conquer the kingdom, test your skills in an all-new mode that spins the game into a unique, challenging experience.<br></li><li>Experience a stunning orchestral score! Hollow Knight’s award-winning composer, Christopher Larkin, returns to bring melancholy melodies, symphonic strings and heart-thumping, soul strumming boss themes to the adventure.</li></ul><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0015_fleur-divider_v02.png?t=1695443850" /><br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0012_5-spage.png?t=1695443850" /><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0014_7-spage.png?t=1695443850" /><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0013_6-spage.png?t=1695443850" />',
                'about_the_game' => '<h2 class="bb_tag">Become the Princess Knight</h2>Play as Hornet, princess-protector of Hallownest, and adventure through a whole new kingdom ruled by silk and song! Captured and brought to this unfamiliar world, Hornet must battle foes and solve mysteries as she ascends on a deadly pilgrimage to the kingdom’s peak.<br><br><br>Hollow Knight: Silksong is the epic sequel to Hollow Knight, the award winning action-adventure. As the lethal hunter Hornet, journey to all-new lands, discover new powers, battle vast hordes of bugs and beasts and uncover ancient secrets tied to your nature and your past.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0010_3-spage.png?t=1695443850" /><h2 class="bb_tag">Game Features</h2><ul class="bb_ul"><li>Discover a whole new kingdom! Explore coral forests, mossy grottos, gilded cities and misted moors as you ascend to the shining citadel at the top of the world.<br></li><li>Engage in lethal acrobatic action! Wield a whole new suite of deadly moves as you dance between foes in deadly, beautiful combat.<br></li><li>Craft powerful tools! Master an ever-expanding arsenal of weapons, traps, and mechanisms to confound your enemies and explore new heights.<br></li><li>Solve shocking quests! Hunt down rare beasts, solve ancient mysteries and search for lost treasures to fulfil the wishes of the downtrodden and restore the kingdom’s hope. Prepare for the unexpected!<br></li><li>Face over 150 all-new foes! Beasts and hunters, assassins and kings, monsters and knights, defeat them all with bravery and skill!<br></li><li>Challenge Silk Soul mode! Once you conquer the kingdom, test your skills in an all-new mode that spins the game into a unique, challenging experience.<br></li><li>Experience a stunning orchestral score! Hollow Knight’s award-winning composer, Christopher Larkin, returns to bring melancholy melodies, symphonic strings and heart-thumping, soul strumming boss themes to the adventure.</li></ul><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0015_fleur-divider_v02.png?t=1695443850" /><br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0012_5-spage.png?t=1695443850" /><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0014_7-spage.png?t=1695443850" /><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/extras/Steam__0013_6-spage.png?t=1695443850" />',
                'short_description' => 'Discover a vast, haunted kingdom in Hollow Knight: Silksong! The sequel to the award winning action-adventure. Explore, fight and survive as you ascend to the peak of a land ruled by silk and song.',
                'supported_languages' => 'Anglais<strong>*</strong>, Français<strong>*</strong>, Italien<strong>*</strong>, Allemand<strong>*</strong>, Espagnol - Espagne<strong>*</strong>, Japonais<strong>*</strong>, Coréen<strong>*</strong>, Portugais du Brésil<strong>*</strong>, Russe<strong>*</strong>, Chinois simplifié<strong>*</strong><br><strong>*</strong>Langues avec support audio complet',
                'header_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/header.jpg?t=1695443850',
                'capsule_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/capsule_231x87.jpg?t=1695443850',
                'capsule_imagev5' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/capsule_184x69.jpg?t=1695443850',
                'website' => 'http://hollowknightsilksong.com/',
                'pc_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"><li><strong>Système d\'exploitation  *:</strong> Windows 7<br></li><li><strong>Processeur :</strong> Intel Core 2 Duo E5200<br></li><li><strong>Mémoire vive :</strong> 4 GB de mémoire<br></li><li><strong>Graphiques :</strong> GeForce 9800GTX+ (1GB)<br></li><li><strong>DirectX :</strong> Version 10<br></li><li><strong>Espace disque :</strong> 9 GB d\'espace disque disponible<br></li><li><strong>Notes supplémentaires :</strong> 1080p, 16:9 recommended</li></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"><li><strong>Système d\'exploitation :</strong> Windows 10<br></li><li><strong>Processeur :</strong> Intel Core i5<br></li><li><strong>Mémoire vive :</strong> 8 GB de mémoire<br></li><li><strong>Graphiques :</strong> GeForce GTX 560+<br></li><li><strong>DirectX :</strong> Version 10<br></li><li><strong>Espace disque :</strong> 9 GB d\'espace disque disponible<br></li><li><strong>Notes supplémentaires :</strong> 1080p, 16:9 recommended</li></ul>',
                ],
                'mac_requirements' => [],
                'linux_requirements' => [],
                'legal_notice' => 'Hollow Knight is © Copyright Team Cherry 2019',
                'developers' => [
                    0 => 'Team Cherry',
                ],
                'publishers' => [
                    0 => 'Team Cherry',
                ],
                'package_groups' => [],
                'platforms' => [
                    'windows' => true,
                    'mac' => false,
                    'linux' => false,
                ],
                'categories' => [
                    0 => [
                        'id' => 2,
                        'description' => 'Solo',
                    ],
                    1 => [
                        'id' => 28,
                        'description' => 'Compat. contrôleurs complète',
                    ],
                ],
                'genres' => [
                    0 => [
                        'id' => '1',
                        'description' => 'Action',
                    ],
                    1 => [
                        'id' => '25',
                        'description' => 'Aventure',
                    ],
                    2 => [
                        'id' => '23',
                        'description' => 'Indépendant',
                    ],
                ],
                'release_date' => [
                    'coming_soon' => true,
                    'date' => 'à déterminer',
                ],
                'support_info' => [
                    'url' => '',
                    'email' => 'support@teamcherry.com.au',
                ],
                'background' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/page_bg_generated_v6b.jpg?t=1695443850',
                'background_raw' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1030300/page_bg_generated.jpg?t=1695443850',
                'content_descriptors' => [
                    'ids' => [],
                    'notes' => null,
                ],
                'ratings' => [
                    'dejus' => [
                        'rating_generated' => '1',
                        'rating' => 'l',
                        'required_age' => '0',
                        'banned' => '0',
                        'use_age_gate' => '0',
                        'descriptors' => 'Violência fantasiosa',
                    ],
                    'steam_germany' => [
                        'rating_generated' => '1',
                        'rating' => '6',
                        'required_age' => '6',
                        'banned' => '0',
                        'use_age_gate' => '0',
                        'descriptors' => 'Fantasy-Gewalt',
                    ],
                ],
            ],
        ];
        // core keeper
        $data['2'] = [
            'success' => true,
            'data' => [
                'type' => 'game',
                'name' => 'Core Keeper',
                'steam_appid' => 1621690,
                'required_age' => 0,
                'is_free' => false,
                'controller_support' => 'full',
                'detailed_description' => '<h1>Feuille de route</h1><p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/CK-Roadmap-DEC24-ENG-big.jpg?t=1733757050" /></p></p><br><h1>More Great Games!</h1><p></p><br><h1>À propos du jeu</h1>Tu te réveilles dans la peau d’un explorateur, au fond d’une caverne regorgeant de secrets inavoués, oubliée depuis la nuit des temps. Dans cette aventure bac à sable primée, conçue pour 1 à 8 joueurs, tes choix façonnent un périple épique dans les méandres d’une mine souterraine. Récolte des reliques et des ressources, fabrique des outils sophistiqués, construis ta base et explore un monde dynamique en constante évolution, qui n’attend que d’être mis au jour. <br><br>Améliore tes habiletés, combats des Titans légendaires et dévoile le pouvoir du Noyau. Cultive ton jardin, pêche dans des eaux mystérieuses, maîtrise un large éventail de recettes, élève et prends soin d’animaux, fais connaissance avec les caverneux et taille ton propre monde unique dans une aventureuse souterraine enchanteresse.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Mining_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>EXPLOITE DES RESSOURCES</strong></li></ul><br>Forge ta propre voie à travers les souterrains. Creuse profondément pour récolter des ressources inestimables et déterrer des pierres précieuses cachées. Pioches, cannes à pêche, pelles, pièges, bombes et mortiers : pour t’aider à survivre, fabrique tous les outils nécessaires. Modernise ton arsenal et ton équipement grâce à des outils sophistiqués, comme le rayon d’anéantissement, et automatise la machinerie pour rationaliser l’exploitation minière, la fusion, le stockage, etc. Améliore tes habiletés et déverrouille des armes puissantes pour conquérir les profondeurs.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Discover_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>DÉCOUVRE UN ANCIEN MONDE</strong></li></ul><br>Explore divers biomes dotés d’écosystèmes uniques et recelant des légendes oubliées depuis longtemps. Des murs vivants des Cavernes d’argile aux cavernes de cristal de la Frontière chatoyante, découvres-en plus sur ce monde mystérieux en dévoilant les secrets des caverneux. Construis un bateau pour naviguer sur la Mer engloutie, parcours le Désert des origines et exhume les restes d’anciennes civilisations.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Craft_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>CONSTRUIS, FABRIQUE ET LIBÈRE TA CRÉATIVITÉ</strong></li></ul><br>Crée une base totalement unique en utilisant différents matériaux et éléments personnalisés. Repaire d’aventurier intrépide ou cottage souterrain tout confort : fais des souterrains ce qui te plaît! Personnalise l’apparence de ton personnage, et profite d’un vaste choix de puissantes armures et tenues uniques pour le préparer au mieux à chaque situation.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Titans_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>AFFRONTE DES TITANS</strong></li></ul><br>Combats des Titans légendaires qui détiennent le secret de ce monde oublié depuis l’aube des temps. Perfectionne tes habiletés au combat contre une variété de monstres et de mini-boss, dépense tes points de talent pour améliorer tes capacités et établis ta stratégie pour conquérir ces créatures souterraines colossales. Découvre des butins rares et puissants pour t’aider dans ton périple et alimenter le Noyau.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Friends_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>EXPLORE AVEC TES AMIS</strong></li></ul><br>Vis cette aventure en solo ou en équipe de 2 à 8 joueurs en mode coopératif drop-in/drop-out en ligne. Joue à ta façon et explore à ton propre rythme : célèbre les saisons, négocie avec des marchands, fais éclore tes nouveaux meilleurs amis, conçois la base la plus stylée, automatise la collecte de ressources, héberge ton propre serveur dédié, crée de la musique sur des instruments faciles à jouer et découvre une aventure bac à sable inoubliable.',
                'about_the_game' => 'Tu te réveilles dans la peau d’un explorateur, au fond d’une caverne regorgeant de secrets inavoués, oubliée depuis la nuit des temps. Dans cette aventure bac à sable primée, conçue pour 1 à 8 joueurs, tes choix façonnent un périple épique dans les méandres d’une mine souterraine. Récolte des reliques et des ressources, fabrique des outils sophistiqués, construis ta base et explore un monde dynamique en constante évolution, qui n’attend que d’être mis au jour. <br><br>Améliore tes habiletés, combats des Titans légendaires et dévoile le pouvoir du Noyau. Cultive ton jardin, pêche dans des eaux mystérieuses, maîtrise un large éventail de recettes, élève et prends soin d’animaux, fais connaissance avec les caverneux et taille ton propre monde unique dans une aventureuse souterraine enchanteresse.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Mining_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>EXPLOITE DES RESSOURCES</strong></li></ul><br>Forge ta propre voie à travers les souterrains. Creuse profondément pour récolter des ressources inestimables et déterrer des pierres précieuses cachées. Pioches, cannes à pêche, pelles, pièges, bombes et mortiers : pour t’aider à survivre, fabrique tous les outils nécessaires. Modernise ton arsenal et ton équipement grâce à des outils sophistiqués, comme le rayon d’anéantissement, et automatise la machinerie pour rationaliser l’exploitation minière, la fusion, le stockage, etc. Améliore tes habiletés et déverrouille des armes puissantes pour conquérir les profondeurs.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Discover_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>DÉCOUVRE UN ANCIEN MONDE</strong></li></ul><br>Explore divers biomes dotés d’écosystèmes uniques et recelant des légendes oubliées depuis longtemps. Des murs vivants des Cavernes d’argile aux cavernes de cristal de la Frontière chatoyante, découvres-en plus sur ce monde mystérieux en dévoilant les secrets des caverneux. Construis un bateau pour naviguer sur la Mer engloutie, parcours le Désert des origines et exhume les restes d’anciennes civilisations.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Craft_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>CONSTRUIS, FABRIQUE ET LIBÈRE TA CRÉATIVITÉ</strong></li></ul><br>Crée une base totalement unique en utilisant différents matériaux et éléments personnalisés. Repaire d’aventurier intrépide ou cottage souterrain tout confort : fais des souterrains ce qui te plaît! Personnalise l’apparence de ton personnage, et profite d’un vaste choix de puissantes armures et tenues uniques pour le préparer au mieux à chaque situation.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Titans_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>AFFRONTE DES TITANS</strong></li></ul><br>Combats des Titans légendaires qui détiennent le secret de ce monde oublié depuis l’aube des temps. Perfectionne tes habiletés au combat contre une variété de monstres et de mini-boss, dépense tes points de talent pour améliorer tes capacités et établis ta stratégie pour conquérir ces créatures souterraines colossales. Découvre des butins rares et puissants pour t’aider dans ton périple et alimenter le Noyau.<br><br><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/extras/Friends_French.gif?t=1733757050" /><br><br><ul class="bb_ul"><li><strong>EXPLORE AVEC TES AMIS</strong></li></ul><br>Vis cette aventure en solo ou en équipe de 2 à 8 joueurs en mode coopératif drop-in/drop-out en ligne. Joue à ta façon et explore à ton propre rythme : célèbre les saisons, négocie avec des marchands, fais éclore tes nouveaux meilleurs amis, conçois la base la plus stylée, automatise la collecte de ressources, héberge ton propre serveur dédié, crée de la musique sur des instruments faciles à jouer et découvre une aventure bac à sable inoubliable.',
                'short_description' => 'Explorez une caverne infinie pleine de créatures, d\'objets et de ressources dans une aventure bac à sable pour 1 à 8 joueurs. Creusez, construisez, combattez, fabriquez et cultivez pour découvrir le mystère du Cœur.',
                'supported_languages' => 'Anglais, Chinois simplifié, Thaï, Allemand, Japonais, Coréen, Espagnol - Espagne, Français, Italien, Portugais du Brésil, Chinois traditionnel, Russe, Ukrainien',
                'reviews' => '“Clever, challenging, and immensely enjoyable... An ore-mining, boss-beating delight”<br>9/10 – TheSixthAxis<br><br>“Worth every penny”<br>9/10 – TechRaptor<br><br>“The ultimate game of exploration and discovery”<br>9/10 – GameSpew<br>',
                'header_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/header.jpg?t=1733757050',
                'capsule_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/5b19828e142f23d88e5213280d8e226408dabbbf/capsule_231x87.jpg?t=1733757050',
                'capsule_imagev5' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/5b19828e142f23d88e5213280d8e226408dabbbf/capsule_184x69.jpg?t=1733757050',
                'website' => null,
                'pc_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"><li>Système d\'exploitation et processeur 64 bits nécessaires<br></li><li><strong>Système d\'exploitation :</strong> Windows 10 64-bit<br></li><li><strong>Processeur :</strong> Intel Core i5-2300 / AMD Ryzen 3 1200<br></li><li><strong>Mémoire vive :</strong> 8 GB de mémoire<br></li><li><strong>Graphiques :</strong> GeForce GTX 460 / Radeon HD 5850</li></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"><li>Système d\'exploitation et processeur 64 bits nécessaires<br></li><li><strong>Système d\'exploitation :</strong> Windows 10 64-bit<br></li><li><strong>Processeur :</strong> Intel Core i5-8400 / AMD Ryzen 7 2700X<br></li><li><strong>Mémoire vive :</strong> 8 GB de mémoire<br></li><li><strong>Graphiques :</strong> GeForce GTX 1050 Ti / Radeon R9 280X</li></ul>',
                ],
                'mac_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"></ul>',
                ],
                'linux_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"><li><strong>Système d\'exploitation :</strong> Linux (Ubuntu 20.04)<br></li><li><strong>Processeur :</strong> Intel Core i5-2300 / AMD Ryzen 3 1200<br></li><li><strong>Graphiques :</strong> GeForce GTX 460 / Radeon HD 5850</li></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"><li><strong>Système d\'exploitation :</strong> Linux (Ubuntu 22.04)<br></li><li><strong>Processeur :</strong> Intel Core i5-8400 / AMD Ryzen 7 2700X<br></li><li><strong>Graphiques :</strong> GeForce GTX 1050 Ti / Radeon R9 280X</li></ul>',
                ],
                'legal_notice' => '© 2023 Pugstorm. All rights reserved. Developed by Pugstorm. Published by Sold Out Sales and Marketing Limited trading as \'Fireshine Games\'. All other copyrights or trademarks are the property of their respective owners and are being used under license.',
                'developers' => [
                    0 => 'Pugstorm',
                ],
                'publishers' => [
                    0 => 'Fireshine Games',
                    1 => 'bilibili',
                ],
                'price_overview' => [
                    'currency' => 'EUR',
                    'initial' => 1999,
                    'final' => 1499,
                    'discount_percent' => 25,
                    'initial_formatted' => '19,99€',
                    'final_formatted' => '14,99€',
                ],
                'packages' => [
                    0 => 1123513,
                    1 => 575770,
                ],

                'platforms' => [
                    'windows' => true,
                    'mac' => false,
                    'linux' => true,
                ],
                'metacritic' => [
                    'score' => 85,
                    'url' => 'https://www.metacritic.com/game/pc/core-keeper?ftag=MCD-06-10aaa1f',
                ],
                'categories' => [
                    0 => [
                        'id' => 2,
                        'description' => 'Solo',
                    ],
                    1 => [
                        'id' => 1,
                        'description' => 'Multijoueur',
                    ],
                    2 => [
                        'id' => 9,
                        'description' => 'Coopération',
                    ],
                    3 => [
                        'id' => 38,
                        'description' => 'Coopération en ligne',
                    ],
                    4 => [
                        'id' => 28,
                        'description' => 'Compat. contrôleurs complète',
                    ],
                    5 => [
                        'id' => 23,
                        'description' => 'Steam Cloud',
                    ],
                    6 => [
                        'id' => 62,
                        'description' => 'Partage familial',
                    ],
                ],
                'genres' => [
                    0 => [
                        'id' => '25',
                        'description' => 'Aventure',
                    ],
                    1 => [
                        'id' => '4',
                        'description' => 'Occasionnel',
                    ],
                    2 => [
                        'id' => '23',
                        'description' => 'Indépendant',
                    ],
                    3 => [
                        'id' => '3',
                        'description' => 'RPG',
                    ],
                ],
                'recommendations' => [
                    'total' => 38013,
                ],
                'release_date' => [
                    'coming_soon' => false,
                    'date' => '27 aout 2024',
                ],
                'support_info' => [
                    'url' => 'https://fireshinegames.co.uk/',
                    'email' => 'https://fireshinegames.co.uk/customer_support/core-keeper/',
                ],
                'background' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/page_bg_generated_v6b.jpg?t=1733757050',
                'background_raw' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1621690/page.bg.jpg?t=1733757050',
                'content_descriptors' => [
                    'ids' => [],
                    'notes' => null,
                ],
                'ratings' => [
                    'dejus' => [
                        'rating' => 'l',
                        'descriptors' => 'Violência fantasiosa',
                    ],
                    'steam_germany' => [
                        'rating_generated' => '1',
                        'rating' => '12',
                        'required_age' => '12',
                        'banned' => '0',
                        'use_age_gate' => '0',
                        'descriptors' => 'Gewalt',
                    ],
                ],
            ],
        ];

        $data['3'] = [
            'success' => true,
            'data' => [
                'type' => 'game',
                'name' => 'SUPERVIVE',
                'steam_appid' => 1283700,
                'required_age' => 0,
                'is_free' => true,
                'detailed_description' => '<h2 class="bb_tag">SUPERVIVE est une combinaison de BR MOBA et de JEU DE TIR AVEC DES HÉROS.</h2><p class="bb_paragraph"> Venez découvrir gratuitement cette première combinaison mondiale de vitesse, précision, combat, stratégie et jeu d\'équipe.</p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-1-v2.gif?t=1732762006" /></p><p class="bb_paragraph"></p><h2 class="bb_tag">CONTENU DU JEU (CETTE LISTE S\'ÉLARGIRA CHAQUE SEMAINE) :</h2><p class="bb_paragraph">* Plongez dans un bac à sable de combat ouvert et débridé où vous devrez sauter, planer, tirer, frapper, rebondir, bombarder et écraser vos ennemis pour être la dernière équipe debout.</p><p class="bb_paragraph">* Modes de jeu multiples : escouades BR (4 joueurs par équipe), duos BR (2 joueurs par équipe), arène (4c4) et modes d\'événements spéciaux.</p><p class="bb_paragraph">* Plus de 16 chasseurs de tempêtes jouables, dont un chasseur pirate en méca-armure, un renard armé d\'un lance-flammes et un anarchiste maîtrisant le pouvoir de la foudre.</p><p class="bb_paragraph"></p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-2-v2.gif?t=1732762006" /></p><p class="bb_paragraph">* UN TAS de pouvoirs et d\'équipements à obtenir à chaque partie pour créer le build parfait : bombardez vos ennemis, dégommez-les avec des lasers, remontez le temps ou transformez-vous en arbre (oui, vous avez bien entendu).</p><p class="bb_paragraph">* Un milliard de façons de revenir et de gagner : aucune exclusion n\'est vraiment définitive.</p><p class="bb_paragraph">* Fruit d\'un studio de développeurs vétérans ayant créé parmi les plus grands jeux JcJ au monde, SUPERVIVE continuera d\'évoluer avec vous pendant des années.</p><p class="bb_paragraph"></p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-3-v2.gif?t=1732762006" /></p><h2 class="bb_tag"></h2>',
                'about_the_game' => '<h2 class="bb_tag">SUPERVIVE est une combinaison de BR MOBA et de JEU DE TIR AVEC DES HÉROS.</h2><p class="bb_paragraph"> Venez découvrir gratuitement cette première combinaison mondiale de vitesse, précision, combat, stratégie et jeu d\'équipe.</p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-1-v2.gif?t=1732762006" /></p><p class="bb_paragraph"></p><h2 class="bb_tag">CONTENU DU JEU (CETTE LISTE S\'ÉLARGIRA CHAQUE SEMAINE) :</h2><p class="bb_paragraph">* Plongez dans un bac à sable de combat ouvert et débridé où vous devrez sauter, planer, tirer, frapper, rebondir, bombarder et écraser vos ennemis pour être la dernière équipe debout.</p><p class="bb_paragraph">* Modes de jeu multiples : escouades BR (4 joueurs par équipe), duos BR (2 joueurs par équipe), arène (4c4) et modes d\'événements spéciaux.</p><p class="bb_paragraph">* Plus de 16 chasseurs de tempêtes jouables, dont un chasseur pirate en méca-armure, un renard armé d\'un lance-flammes et un anarchiste maîtrisant le pouvoir de la foudre.</p><p class="bb_paragraph"></p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-2-v2.gif?t=1732762006" /></p><p class="bb_paragraph">* UN TAS de pouvoirs et d\'équipements à obtenir à chaque partie pour créer le build parfait : bombardez vos ennemis, dégommez-les avec des lasers, remontez le temps ou transformez-vous en arbre (oui, vous avez bien entendu).</p><p class="bb_paragraph">* Un milliard de façons de revenir et de gagner : aucune exclusion n\'est vraiment définitive.</p><p class="bb_paragraph">* Fruit d\'un studio de développeurs vétérans ayant créé parmi les plus grands jeux JcJ au monde, SUPERVIVE continuera d\'évoluer avec vous pendant des années.</p><p class="bb_paragraph"></p><p class="bb_paragraph"><img class="bb_img" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/extras/Steam-Gif-3-v2.gif?t=1732762006" /></p><h2 class="bb_tag"></h2>',
                'short_description' => 'UN BATTLE ROYALE MOBA ALLIÉ À UN JEU DE TIR AVEC DES HÉROS. Sautez, planez, tirez, frappez, rebondissez, bombardez et écrasez vos ennemis sur ce champ de bataille aérien chaotique et fluide. Combattez en équipe multi-escouades, tuez des boss pour leur butin, remportez la victoire et recommencez.',
                'supported_languages' => 'Anglais<strong>*</strong>, Français, Italien, Allemand, Espagnol - Espagne, Polonais, Turc, Russe, Chinois traditionnel, Chinois simplifié, Portugais du Brésil<br><strong>*</strong>Langues avec support audio complet',
                'header_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/header.jpg?t=1732762006',
                'capsule_image' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/ffd4a9ce4af75b5833a44ddabe98bcd4f4cb72dc/capsule_231x87.jpg?t=1732762006',
                'capsule_imagev5' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/ffd4a9ce4af75b5833a44ddabe98bcd4f4cb72dc/capsule_184x69.jpg?t=1732762006',
                'website' => null,
                'pc_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"><li>Système d\'exploitation et processeur 64 bits nécessaires<br></li><li><strong>Système d\'exploitation :</strong> Windows 10 64-bit build 1909.1350 or newer<br></li><li><strong>Processeur :</strong> Intel i5-4440 (3.1GHz) - AMD Ryzen 3 3100 (3.6GHz) - 4 physical cores<br></li><li><strong>Mémoire vive :</strong> 8 GB de mémoire<br></li><li><strong>Graphiques :</strong> GeForce GTX 980ti or equivalent<br></li><li><strong>DirectX :</strong> Version 11<br></li><li><strong>Réseau :</strong> connexion internet haut débit<br></li><li><strong>Espace disque :</strong> 8 GB d\'espace disque disponible</li></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"><li>Système d\'exploitation et processeur 64 bits nécessaires<br></li><li><strong>Système d\'exploitation :</strong> Windows 10 64-bit build 1909.1350 or newer<br></li><li><strong>Processeur :</strong> Intel Core i5-9600 - AMD Ryzen 5 3500 or equivalent<br></li><li><strong>Mémoire vive :</strong> 8 GB de mémoire<br></li><li><strong>Graphiques :</strong> Nvidia RTX 2070 - Radeon RX 5700 XT or equivalent - DirectX 12<br></li><li><strong>DirectX :</strong> Version 12<br></li><li><strong>Réseau :</strong> connexion internet haut débit<br></li><li><strong>Espace disque :</strong> 10 GB d\'espace disque disponible</li></ul>',
                ],
                'mac_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"></ul>',
                ],
                'linux_requirements' => [
                    'minimum' => '<strong>Minimale :</strong><br><ul class="bb_ul"></ul>',
                    'recommended' => '<strong>Recommandée :</strong><br><ul class="bb_ul"></ul>',
                ],
                'developers' => [
                    0 => 'Theorycraft Games',
                ],
                'publishers' => [
                    0 => 'Theorycraft Games',
                ],
                'package_groups' => [],
                'platforms' => [
                    'windows' => true,
                    'mac' => false,
                    'linux' => false,
                ],
                'categories' => [
                    0 => [
                        'id' => 1,
                        'description' => 'Multijoueur',
                    ],
                    1 => [
                        'id' => 49,
                        'description' => 'PvP',
                    ],
                    2 => [
                        'id' => 36,
                        'description' => 'JcJ en ligne',
                    ],
                    3 => [
                        'id' => 35,
                        'description' => 'Achats en jeu',
                    ],
                ],
                'genres' => [
                    0 => [
                        'id' => '1',
                        'description' => 'Action',
                    ],
                    1 => [
                        'id' => '37',
                        'description' => 'Free-to-play',
                    ],
                    2 => [
                        'id' => '70',
                        'description' => 'Accès anticipé',
                    ],
                ],
                'release_date' => [
                    'coming_soon' => false,
                    'date' => '20 nov. 2024',
                ],
                'support_info' => [
                    'url' => 'https://theorycraft.helpshift.com',
                    'email' => '',
                ],
                'background' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/page_bg_generated_v6b.jpg?t=1732762006',
                'background_raw' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1283700/page.bg.jpg?t=1732762006',
                'content_descriptors' => [
                    'ids' => [],
                    'notes' => null,
                ],
                'ratings' => [
                    'dejus' => [
                        'rating_generated' => '1',
                        'rating' => '10',
                        'required_age' => '10',
                        'banned' => '0',
                        'use_age_gate' => '0',
                        'descriptors' => 'Violência',
                    ],
                    'steam_germany' => [
                        'rating_generated' => '1',
                        'rating' => '12',
                        'required_age' => '12',
                        'banned' => '0',
                        'use_age_gate' => '0',
                        'descriptors' => 'Gewalt',
                    ],
                ],
            ],
        ];

        return $this->generateMockResponse(array_key_exists($id, $data) ? [$id => $data[$id]] : [$id => ['success' => false]]);
    }
}
