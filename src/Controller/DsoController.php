<?php

namespace App\Controller;

use App\Classes\CacheInterface;
use App\Classes\Utils;
use App\Controller\ControllerTraits\DsoTrait;
use App\Entity\AbstractEntity;
use App\Entity\Dso;
use App\Entity\ListDso;
use App\Managers\DsoManager;
use App\Repository\DsoRepository;
use Astrobin\Exceptions\WsResponseException;
use Astrobin\Response\Image;
use Astrobin\Response\ListImages;
use Astrobin\Services\GetImage;
use Elastica\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DsoController
 * @package App\Controller
 */
class DsoController extends AbstractController
{
    const DEFAULT_PAGE = 1;

    use DsoTrait;

    /** @var CacheInterface  */
    private $cacheUtil;
    /** @var DsoManager  */
    private $dsoManager;
    /** @var DsoRepository  */
    private $dsoRepository;
    /** @var TranslatorInterface  */
    private $translatorInterface;

    /**
     * DsoController constructor.
     *
     * @param CacheInterface $cacheUtil
     * @param DsoManager $dsoManager
     * @param DsoRepository $dsoRepository
     * @param TranslatorInterface $translatorInterface
     */
    public function __construct(CacheInterface $cacheUtil, DsoManager $dsoManager, DsoRepository $dsoRepository, TranslatorInterface $translatorInterface)
    {
        $this->cacheUtil = $cacheUtil;
        $this->dsoManager = $dsoManager;
        $this->dsoRepository = $dsoRepository;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * @Route({
     *  "en": "/catalog/{id}",
     *  "fr": "/catalogue/{id}",
     *  "es": "/catalogo/{id}",
     *  "pt": "/catalogo/{id}",
     *  "de": "/katalog/{id}"
     * }, name="dso_show")
     *
     * @param string $id
     *
     * @return Response
     * @throws \Astrobin\Exceptions\WsException
     * @throws \ReflectionException
     */
    public function show(Request $request, string $id): Response
    {
        $params = [];

        $id = explode(trim(AbstractEntity::URL_CONCAT_GLUE), $id);
        $id = reset($id);

        /** @var Dso $dso */
        $dso = $this->dsoManager->buildDso($id);
        $params['desc'] = implode(Utils::GLUE_DASH, $dso->getDesigs());

        if (!is_null($dso)) {
            $params['dsoData'] = $this->dsoManager->formatVueData($dso);
            $params['constTitle'] = $this->dsoManager->buildTitleConstellation($dso->getConstId());
            $params['title'] = $this->dsoManager->buildTitle($dso);

            // Image cover
            $params['imgCover'] = $dso->getImage();
            $params['imgCoverUser'] = $dso->getAstrobinUser();

            // List of Dso from same constellation
            /** @var ListDso $listDso */
            $listDso = $this->dsoManager->getListDsoFromConst($dso, 20);
            $params['dso_by_const'] = $this->dsoManager->buildListDso($listDso);

            $params['list_types_filters'] = $this->buildFiltersWithAll($listDso) ?? [];

            // Map
            $params['geojsonDso'] = [
                "type" => "FeatureCollection",
                "features" =>  [$this->dsoManager->buildgeoJson($dso)]
            ];
            $params['constId'] = $dso->getConstId();
            $params['centerMap'] = $dso->getGeometry()['coordinates'];

            // Images
            try {
                $params['images'] = [];
                if ($this->cacheUtil->hasItem(md5($id . '_list_images'))) {
                    $params['images'] = unserialize($this->cacheUtil->getItem(md5($id . '_list_images')));
                } else {
                    $params['images'] = $this->getListImages($dso->getId());
                }
            } catch (WsResponseException $e) {}
        } else {
            throw new NotFoundException();
        }

        /** @var Response $response */
        $response = $this->render('pages/dso.html.twig', $params);
        $response->setPublic();
        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->set('x-dso-id', implode(' ', [$dso->getElasticId(), md5(sprintf('%s_%s', $id, $request->getLocale()))]));

        return $response;
    }


    /**
     * Retrieve list of images for carousel

     * @param $dsoId
     * @param CacheInterface $cacheUtil
     *
     * @return array
     * @throws WsResponseException
     * @throws \Astrobin\Exceptions\WsException
     * @throws \ReflectionException
     */
    private function getListImages($dsoId)
    {
        $tabImages = [];

        /** @var GetImage $astrobinWs */
        $astrobinWs = new GetImage();

        /** @var ListImages|Image $listImages */
        $listImages = $astrobinWs->getImagesBySubject($dsoId, 5);

        if ($listImages instanceof Image) {
            $tabImages = $listImages->url_regular;

        } elseif ($listImages instanceof ListImages && 0 < $listImages->count) {
            $tabImages = array_map(function (Image $image) {
                return $image->url_regular;
            }, iterator_to_array($listImages));
        }

        $this->cacheUtil->saveItem(md5($dsoId . '_list_images'), serialize($tabImages));
        return $tabImages;
    }

    /**
     * @Route("/geodata/dso/{id}", name="dso_geo_data", options={"expose": true})
     * @param string $id
     *
     * @return JsonResponse
     * @throws \Astrobin\Exceptions\WsException
     * @throws \ReflectionException
     */
    public function geoJson(string $id): JsonResponse
    {
        /** @var Dso $dso */
        $dso = $this->dsoManager->buildDso($id);

        $geoJsonData = $this->dsoManager->buildgeoJson($dso);

        /** @var JsonResponse $jsonResponse */
        $jsonResponse = new JsonResponse($geoJsonData, Response::HTTP_OK);
        $jsonResponse->setPublic();
        $jsonResponse->setSharedMaxAge(0);

        return $jsonResponse;
    }


    /**
     * @Route({
     *  "en": "/catalog",
     *  "fr": "/catalogue",
     *  "es": "/catalogo",
     *  "pt": "/catalogo",
     *  "de": "/katalog"
     * }, name="dso_catalog")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \ReflectionException
     */
    public function catalog(Request $request): Response
    {
        $page = self::DEFAULT_PAGE;
        $from = DsoRepository::FROM;
        $filters = $listAggregations = [];
        $ordering = Utils::getOrderCatalog();

        /** @var Router $router */
        $router = $this->get('router');

        if ($request->query->has('page')) {
            $page = (int)filter_var($request->query->get('page'), FILTER_SANITIZE_NUMBER_INT);
            if (is_int($page)) {
                $from = (DsoRepository::SIZE)*($page-1);
            }
        }

        if (0 < $request->query->count()) {
            $authorizedFilters = $this->dsoRepository->getListAggregates(true);

            // Removed unauthorized keys
            $filters = array_filter($request->query->all(), function($key) use($authorizedFilters) {
                return in_array($key, $authorizedFilters);
            }, ARRAY_FILTER_USE_KEY);

            // Sanitize data (todo : try better)
            array_walk($filters, function (&$value, $key) {
                $value = filter_var($value, FILTER_SANITIZE_STRING);
            });
        }

        // Search results
        list($listDso, $listAggregates, $nbItems) = $this->dsoRepository->setLocale($request->getLocale())->getObjectsCatalogByFilters($from, $filters);

        // List facets
        $allQueryParameters = $request->query->all();
        foreach ($listAggregates as $type => $listFacets) {
            $typeTr = $this->translatorInterface->trans($type, ['%count%' => count($listFacets)]);

            $listFacetsByType = array_map(function($facet) use ($router, $allQueryParameters, $type) {
                return [
                    'code' => key($facet),
                    'value' => $this->translatorInterface->trans(sprintf('%s.%s', $type, strtolower(key($facet)))),
                    'number' => reset($facet),
                    'full_url' => $router->generate('dso_catalog', array_merge($allQueryParameters, [$type => key($facet)]))
                ];
            }, $listFacets);

            $routeDelete = '';
            if (in_array($type, array_keys($filters))) {
                $routeDelete = $router->generate(
                  'dso_catalog',
                    array_diff_key(
                        $request->query->all(),
                        [$type => $filters[$type]]
                    )
                );
            }


            // Sort here because dont know ho to do in aggregates query...
            // Specific sort for catalog
            if ('catalog' === $type) {
                usort($listFacetsByType, function($facetA, $facetB) use ($ordering) {
                    return (array_search($facetA['code'], $ordering) > array_search($facetB['code'], $ordering));
                });
            } elseif ('constellation' === $type) {
                usort($listFacetsByType, function($kFacetA, $kFacetB) {
                    return strcmp($kFacetA['code'], $kFacetB['code']);
                });
            }

            $listAggregations[$type] = [
                'name' => $typeTr,
                'delete_url' => $routeDelete,
                'list' => $listFacetsByType
            ];
        }

        // Params
        $result['list_dso'] = $this->dsoManager->buildListDso($listDso);
        $result['list_facets'] = $listAggregations;
        $result['nb_items'] = $nbItems;
        $result['current_page'] = $page;
        $result['nb_pages'] = $nbPages = ceil($nbItems/DsoRepository::SIZE);

        $queryAll = $request->query->all();
        $result['filters'] = call_user_func("array_merge", array_map(function($val, $key) use($router, $queryAll) {
            return [
                'label' => $this->translatorInterface->trans(sprintf('%s.%s', $key, strtolower($val))),
                'delete_url' => $router->generate('dso_catalog', array_diff_key($queryAll, [$key => $val]))
            ];
        }, $filters, array_keys($filters)));

        unset($queryAll['page']);
        $result['pagination'] = [
          'prev' => (self::DEFAULT_PAGE < $page) ? $router->generate('dso_catalog', array_merge($queryAll, ['page' => $page-1])): null,
          'next' => ($nbPages > $page) ? $router->generate('dso_catalog', array_merge($queryAll, ['page' => $page+1])): null
        ];

        // Description
        $result['pageDesc'] = $this->translatorInterface->trans('filteringList');
        if ($request->query->has('catalog')) {
            $catalog = $request->query->get('catalog');
            $desc = $this->translatorInterface->trans('description.' . $catalog);
            if (!empty($desc) && $desc !== 'description.' . $catalog) {
                $result['pageDesc'] = $desc;
            }
        }

        /** @var Response $response */
        $response = $this->render('pages/catalog.html.twig', $result);
        $response->setPublic();
        $response->setSharedMaxAge(0);

        return $response;
    }
}
