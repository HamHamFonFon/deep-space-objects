<?php

namespace App\Controller;

use App\Entity\Constellation;
use App\Repository\ConstellationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConstellationController
 * @package App\Controller
 */
class ConstellationController extends AbstractController
{

    /**
     * @Route("/constellation/{id}", name="constellation_show")
     * @param string $id
     * @param ConstellationRepository $constellationRepository
     * @return Response
     */
    public function show(string $id, ConstellationRepository $constellationRepository): Response
    {
        $result = [];

        /** @var Constellation $constellation */
        $constellation = $constellationRepository->getObjectById($id);

        dump($constellation);
        $result['constellation'] = $constellation;
        /** @var Response $response */
        $response = $this->render('pages/constellation.html.twig', $result);
        $response->headers->set('X-Constellation-Id', $constellation->getElasticId());
        $response->setPublic();

        return $response;
    }


    /**
     * @Route("/constellations", name="constellation_list")
     * @return Response
     */
    public function list()
    {
        return new Response("coucou");
    }

}
