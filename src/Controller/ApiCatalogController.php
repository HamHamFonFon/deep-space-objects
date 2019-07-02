<?php

namespace App\Controller;

use App\Entity\Dso;
use App\Managers\DsoManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DsoController
 *
 * @package App\Api\Controller
 */
final class ApiCatalogController extends AbstractFOSRestController
{
    /** @var DsoManager  */
    private $dsoManager;

    /**
     * DsoController constructor.
     *
     * @param DsoManager $dsoManager
     */
    public function __construct(DsoManager $dsoManager)
    {
        $this->dsoManager = $dsoManager;
    }

    /**
     * @param string $id
     *
     * @return Response
     * @throws \Astrobin\Exceptions\WsException
     * @throws \ReflectionException
     *
     * @Rest\Get("/item/{$dsoId}", name="api_item_dso")
     */
    public function getItem($dsoId)
    {
        /** @var Dso $dso */
        $dso = $this->dsoManager->buildDso($dsoId);
        if (!$dso instanceof Dso) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($dso));
    }

}
