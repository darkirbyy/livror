<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\SearchModeEnum;
use App\Service\AutocompletionHelper;
use App\Service\AutocompletionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/steam', name: 'steam_')]
class SteamController extends AbstractController
{
    // Autocomplete a game name thanks to the steam game list
    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, AutocompletionManager $autocompletionManager, AutocompletionHelper $autocompletionHelper): Response
    {
        $search = $request->query->get('query');
        $searchMode = $request->query->getEnum('mode', SearchModeEnum::class, SearchModeEnum::LIKE);

        $objects = $autocompletionManager->fromSteam($search, $searchMode);

        return $this->json($autocompletionHelper->renderItems('steam/autocomplete.html.twig', $objects));
    }
}
