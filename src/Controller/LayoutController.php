<?php

namespace App\Controller;

use App\Entity\DTO\ConstellationDTO;
use App\Entity\DTO\DTOInterface;
use App\Managers\ConstellationManager;
use App\Managers\DsoManager;
use App\Repository\DsoRepository;
use AstrobinWs\Exceptions\WsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LayoutController
 * @package App\Controller
 */
class LayoutController extends AbstractController
{
    public const HTTP_TTL = 31556952;

    /** @var TranslatorInterface  */
    private TranslatorInterface $translator;

    /** @var DsoRepository */
    private DsoRepository $dsoRepository;

    /**
     * LayoutController constructor.
     *
     * @param TranslatorInterface $translator
     * @param DsoRepository $dsoRepository
     */
    public function __construct(TranslatorInterface $translator, DsoRepository $dsoRepository)
    {
        $this->translator = $translator;
        $this->dsoRepository = $dsoRepository;
    }

    /**
     * Header
     *
     * @param Request $request
     * @param String $listLocales
     *
     * @return Response
     */
    public function header(Request $request, string $listLocales): Response
    {
        /** @var Request $mainRequest */
        $mainRequest = $this->get('request_stack')->getMasterRequest();

        /** @var Router $router */
        $router = $this->get('router');
        $currentLocale = $mainRequest->getLocale() ?? 'en';

        $listLocales = array_filter(explode('|', $listLocales), function($value) use ($currentLocale) {
            return !empty($value) && ($value !== $currentLocale);
        });

        $mainRoute = $mainRequest->get('_route') ?? 'homepage';

        $routeParams = $mainRequest->get('_route_params') ?? [];
        $paramsRoute = array_merge($routeParams, $mainRequest->query->all()) ?? [];

        $result = [
            '_route' => $mainRoute,
            'listLocales' => array_map(function($locale) use ($router, $mainRoute, $paramsRoute) {
                $paramsRoute['_locale'] = $locale;
                return [
                    'locale' => $locale,
                    'label' => $this->translator->trans($locale),
                    'flag' => sprintf('flag_%s', $locale),
                    'path' => $router->generate(sprintf('%s.%s', $mainRoute, $locale), $paramsRoute)
                ];
            }, $listLocales),
            'currentLocale' => $currentLocale,
            'leftSideMenu' => $this->buildMenu($currentLocale, ['lastUpdate' ,'catalog', 'constellation', 'observations', 'addObservations', 'scheduleObs']),
            'notification' => [
                'label' => $this->translator->trans('last_update_title'),
                'path' => $router->generate(sprintf('last_update_dso.%s', $currentLocale))
            ],
            'constellation' => [
                'label' => $this->translator->trans('constId', ['%count%' => 2]),
                'path' => $router->generate(sprintf('constellation_list.%s', $currentLocale))
            ],
            'menuData' => $this->buildMenu($currentLocale, ['catalog', 'map']),
            'routeSearch' => $router->generate(sprintf('search_ajax.%s', $currentLocale), ['_locale' => $currentLocale])
        ];

        /** @var Response $response */
        $response = new Response();
        $response->setSharedMaxAge(self::HTTP_TTL);

        return $this->render('includes/layout/header.html.twig', $result, $response);
    }


    /**
     * Build left side menu
     *
     * @param string $locale
     * @param array $listKeysMenu
     * @return array
     */
    private function buildMenu(string $locale, array $listKeysMenu): array
    {
        /** @var Router $routerInterface */
        $routerInterface = $this->get('router');

        $menu = [
            'lastUpdate' => [
                'label' => $this->translator->trans('last_update_title'),
                'path' => $routerInterface->generate(sprintf('last_update_dso.%s', $locale)),
                'icon_class' => 'bell'
            ],
            'catalog' => [
                'label' => $this->translator->trans('catalogs'),
                'path' => $routerInterface->generate(sprintf('dso_catalog.%s', $locale)),
                'icon_class' => 'shape',
                'subMenu' => $this->buildSubMenu($locale, ['messier', 'ngc', 'ic', 'sh'])
            ],
            /*'constellation' => [
                'label' => $this->translator->trans('constId', ['%count%' => 2]),
                'path' => $routerInterface->generate(sprintf('constellation_list.%s', $locale)),
                'icon_class' => 'constellation'
            ],*/
            'map' => [
                'label' => $this->translator->trans('skymap'),
                'path' => $routerInterface->generate(sprintf('skymap.%s', $locale)),
                'icon_class' => 'planet'
            ],
            /*'observations' => [
                'label' => $this->translator->trans('listObservations'),
                'path' => $routerInterface->generate(sprintf('observation_list.%s', $locale)),
                'icon_class' => 'telescop'
            ],
            'scheduleObs' => [
                'label' => $this->translator->trans('scheduleObs'),
                'path' => $routerInterface->generate(sprintf('schedule_obs.%s', $locale)),
                'icon_class' => 'add-observation'
            ],
            'addObservations' => [
                'label' => $this->translator->trans('addObservation'),
                'path' => $routerInterface->generate(sprintf('add_observation.%s', $locale)),
                'icon_class' => 'calendar'
            ],*/
            'contact' => [
                'label' => $this->translator->trans('contact.title'),
                'path' => $routerInterface->generate(sprintf('contact.%s', $locale)),
                'icon_class' => 'contact'
            ]
        ];

        return array_filter($menu, static function ($key) use ($listKeysMenu) {
            return in_array($key, $listKeysMenu, true);
        }, ARRAY_FILTER_USE_KEY);

    }

    /**
     * @param string $locale
     * @param $listCatalogs
     *
     * @return array
     */
    public function buildSubMenu(string $locale, array $listCatalogs): array
    {
        /** @var Router $routerInterface */
        $routerInterface = $this->get('router');

        return array_map(function(string $catalog) use($routerInterface, $locale) {
            return [
                'label' => $this->translator->trans(sprintf('catalog.%s', $catalog)),
                'path' => $routerInterface->generate(sprintf('dso_catalog_redirect.%s', $locale), ['catalog' => $catalog])
            ];

        }, $listCatalogs);
    }

    /**
     * Footer
     *
     * @param Request $request
     * @param string|null $githubLink
     * @param string|null $paypalLink
     * @param string|null $facebookLink
     * @param string|null $twitterLink
     *
     * @return Response
     * @deprecated
     */
    public function footer(Request $request, ?string $githubLink, ?string $paypalLink, ?string $facebookLink, ?string $twitterLink): Response
    {
        /** @var Request $mainRequest */
        $mainRequest = $this->get('request_stack')->getMasterRequest();
        $mainRoute = $mainRequest->get('_route');

        /** @var Router $routerInterface */
        $routerInterface = $this->get('router');

        $result['share'] = $this->ctaFooter($githubLink, $facebookLink, $twitterLink);

        $result['links_footer'] = [
            'api' => [
                'label' => 'API',
                'path' => $routerInterface->generate(sprintf('help_api_page.%s', $request->getLocale()))
            ],
            'legal_notice' => [
                'label' => $this->translator->trans('legal_notice.title'),
                'path' => $routerInterface->generate(sprintf('legal_notice.%s', $request->getLocale())),
            ],
            'contact' => [
                'label' => $this->translator->trans('contact.title'),
                'path' => $routerInterface->generate(sprintf('contact.%s', $request->getLocale())),
            ],
            'support' => [
                'label' => $this->translator->trans('support.title'),
                'path' => $routerInterface->generate(sprintf('help_astro-otter.%s', $request->getLocale())),
            ]
        ];

        $result['main_route'] = $mainRoute;

        /** @var Response $response */
        $response = new Response();
        $response->setSharedMaxAge(0);

        return $this->render('includes/layout/footer.html.twig', $result, $response);
    }


    /**
     * @param string|null $githubLink
     * @param string|null $facebookLink
     * @param string|null $twitterLink
     *
     * @return array
     */
    private function ctaFooter(?string $githubLink, ?string $facebookLink, ?string $twitterLink): array
    {
        $tab = [];

        if ($facebookLink) {
            $tab['facebook'] = [
                'label' => ucfirst('facebook'),
                'path' => $facebookLink,
                'blank' => true,
                'icon_class' => 'facebook'
            ];
        }

        if ($twitterLink) {
            $tab['twitter'] = [
                'label' => ucfirst('twitter'),
                'path' => $twitterLink,
                'blank' => true,
                'icon_class' => 'twitter'
            ];
        }

        if ($githubLink) {
            $tab['github'] = [
                'label' => ucfirst('github'),
                'path' => $githubLink,
                'blank' => true,
                'icon_class' => 'github'
            ];
        }
        return $tab;
    }

    /**
     * @Route("/sitemap.xml", name="sitemap", defaults={"_format"="xml"})
     *
     * @param Request $request
     * @param string $listLocales
     * @param DsoManager $dsoManager
     * @param ConstellationManager $constellationManager
     *
     * @return Response
     * @throws WsException
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function sitemap(Request $request, string $listLocales, DsoManager $dsoManager, ConstellationManager $constellationManager): Response
    {
        $params = [];

        $currentLocal = $request->getLocale();
        $listLocales = array_filter(explode('|', $listLocales), static function($value) use ($currentLocal) {
            return !empty($value) && ($value !== $currentLocal);
        });

        /** @var Router $router */
        $router = $this->get('router');

        // Static pages
        $params['urls'] = [
            'home' => [
                'loc' => $router->generate('homepage', [],Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('homepage.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'catalog' => [
                'loc' => $router->generate('dso_catalog', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('dso_catalog.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'constellation_list' => [
                'loc' => $router->generate('constellation_list', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('constellation_list.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'contact' => [
                'loc' => $router->generate('contact', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('contact.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'skymap' => [
                'loc' => $router->generate('skymap', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('skymap.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales)),
            ],
            'last_udapte' => [
                'loc' => $router->generate('last_update_dso', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('last_update_dso.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'help_api' => [
                'loc' => $router->generate('help_api_page', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('help_api_page.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'legalnotice' => [
                'loc'=> $router->generate('legal_notice', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('legal_notice.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ],
            'helpus' => [
                'loc'=> $router->generate('help_astro-otter', [], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router) {
                    return [$locale => $router->generate(sprintf('help_astro-otter.%s', $locale), ['_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ]
        ];


        /** @var array $listDsoMessier */
        [$listDsoMessier,,] = $this->dsoRepository->getObjectsCatalogByFilters(0, ['catalog' => 'messier'], 110, true);

        /** @var array $listDsoNgc */
        [$listDsoNgc,,] = $this->dsoRepository->getObjectsCatalogByFilters(0, ['catalog' => 'ngc'], 8000, true);

        /** @var DTOInterface $dso */
        foreach ($listDsoMessier as $dsoId) {
            $dso = $dsoManager->getDso($dsoId);
            $params['urls'][$dso->getId()] = [
                'loc' => $dso->absoluteUrl(),
                'urlLoc' => array_merge(...array_map(function ($locale) use ($dsoManager, $dsoId) {
                    $dsoLocal = $dsoManager->getDsoFromCache(sprintf('%s_%s', $dsoId, $locale)) ?? $this->dsoRepository->setLocale($locale)->getObjectById($dsoId);
                    return [$locale => $dsoLocal->absoluteUrl()];
                }, $listLocales)),
                'lastmod' => $dso->getUpdatedAt()->format('Y-m-d')
            ];
        }

        /**foreach ($listDsoNgc as $dso) {
            $params['urls'][$dso->getId()] = [
                'loc' => $dso->fullUrl(),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($dso) {
                    return [$locale => $dso->fullUrl()];
                }, $listLocales)),
                'lastmod' => $dso->getUpdatedAt()->format('Y-m-d')
            ];
        }*/


        $listConstellation = $constellationManager->getAllConstellations();

        /** @var ConstellationDTO $constellation */
        foreach ($listConstellation as $constellation) {
            $params['urls'][$constellation->getId()] = [
                'loc' => $constellation->absoluteUrl(),
                'lastmod' => null
                /*'urlLoc' => array_merge(...array_map(function ($locale) use ($constellation) {
                    return [$locale => $this->urlGenerateHelper->generateUrl($constellation, Router::ABSOLUTE_URL, $locale)];
                }, $listLocales))*/
            ];
        }

        foreach (['messier', 'ngc', 'ic', 'sh'] as $catalog) {
            $params['urls']['catalog_'.$catalog] = [
                'loc' => $router->generate('dso_catalog_redirect', ['catalog' => $catalog], Router::ABSOLUTE_URL),
                'urlLoc' => array_merge(...array_map(static function ($locale) use ($router, $catalog) {
                    return [$locale => $router->generate(sprintf('dso_catalog_redirect.%s', $locale), ['catalog' => $catalog, '_locale' => $locale], Router::ABSOLUTE_URL)];
                }, $listLocales))
            ];
        }

        $xml = $this->renderView('sitemap.xml.twig', $params);

        /** @var Response $response */
        $response = new Response($xml, Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/xml');
        $response->setPublic();
        $response->setSharedMaxAge(0);

        return $response;
    }


    /**
     * @Route("/load/data/{file}", name="data_celestial")
     * @param Request $request
     * @param KernelInterface $kernel
     * @param string $file
     *
     * @return JsonResponse
     * @throws \JsonException
     */
    public function getStarsFromConst(Request $request, KernelInterface $kernel, string $file): JsonResponse
    {
        preg_match('/stars.([A-Za-z]{3}|([0-9]{1,2})).json/', $file, $matches);
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => []
        ];

        if ($matches) {
            $match = $matches[1];
            if (in_array($match, [6, 8, 14], true)) {
                $fileJson = file_get_contents($kernel->getProjectDir() . '/public/build/data/' . sprintf('stars.%d.json', $match));

                $geojson = json_decode($fileJson, true, 512, JSON_THROW_ON_ERROR);
            } else {
                /** @var \Generator $readFile */
                /*$readFile = function($file) {
                    $h = fopen($file, 'r+');
                    while(!feof($h)) {
                        yield fgets($h);
                    }
                    fclose($h);
                };

                $fileJson = $readFile($kernel->getProjectDir() . '/public/build/data/stars.14.json');*/

                $fileJson = file_get_contents($kernel->getProjectDir() . '/public/build/data/stars.8.json');
                $dataJson = json_decode($fileJson, true, 512, JSON_THROW_ON_ERROR);
                $filteredStars = array_filter($dataJson['features'], static function ($starData) use ($match) {
                    return $match === $starData['properties']['con'];
                });

                $geojson = [
                    'type' => 'FeatureCollection',
                    'features' => array_values($filteredStars)
                ];
            }
        } else {
            $filePath = $kernel->getProjectDir() . sprintf('/public/build/data/%s', $file);
            if (file_exists($filePath)) {
                $fileJson = file_get_contents($filePath);
                $geojson = json_decode($fileJson, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        /** @var JsonResponse $jsonResponse */
        $jsonResponse = new JsonResponse($geojson, Response::HTTP_OK);
        $jsonResponse->setSharedMaxAge(self::HTTP_TTL);
        $jsonResponse->setPublic();

        return $jsonResponse;
    }

}
