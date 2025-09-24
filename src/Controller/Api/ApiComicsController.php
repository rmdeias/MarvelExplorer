<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ComicRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiComicsController extends AbstractController
{
    #[Route('/api/comics', name: 'api_comics', methods: ['GET'])]
    public function list(ComicRepository $comicRepository, Request $request, Packages $assetsHelper): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10); // nombre max de comics
        $sort = $request->query->get('sort', 'recent'); // recent ou alpha
        $search = $request->query->get('search');      // recherche sur le titre

        $qb = $comicRepository->createQueryBuilder('c');

        // Filtre recherche
        if ($search) {
            $qb->andWhere('c.title LIKE :search AND c.title NOT LIKE :variant AND c.title NOT LIKE :paperback AND c.title NOT LIKE :hardcover')
                ->setParameter('search', "%$search%")
                ->setParameter('variant', "%variant%")
                ->setParameter('paperback', "%paperback%")
                ->setParameter('hardcover', "%hardcover%");
            $limit = 100;
        }

        // Tri
        if ($sort === 'recent') {
            $today = new \DateTime(); // date actuelle
            $qb->andWhere('c.date <= :today  AND c.title NOT LIKE :variant AND c.title NOT LIKE :paperback AND c.title NOT LIKE :hardcover')
                ->setParameter('today', $today)
                ->setParameter('variant', "%variant%")
                ->setParameter('paperback', "%paperback%")
                ->setParameter('hardcover', "%hardcover%")
                ->orderBy('c.date', 'DESC');
        }

        $qb->setMaxResults($limit);

        $comics = $qb->getQuery()->getResult();

        if ($search !== 'search') {
            usort($comics, function ($a, $b) {
                return strnatcasecmp($a->getTitle(), $b->getTitle());
            });
        }
        // Transformation pour JSON
        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'title' => $c->getTitle(),
            'thumbnail' => $c->getThumbnail() ?: $assetsHelper->getUrl('assets/images/comic-no-img.jpg'),
            'date' => $c->getDate(),
        ], $comics);

        return $this->json(['data' => $data]);
    }
}
