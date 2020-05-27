<?php

namespace App\Controller;

use App\Classes\Utils;
use App\Entity\BDD\ApiUser;
use App\Entity\BDD\Contact;
use App\Entity\ES\Dso;
use App\Forms\ContactFormType;
use App\Forms\RegisterApiUsersFormType;
use App\Managers\DsoManager;
use App\Repository\DsoRepository;
use App\Service\MailService;
use App\Service\SocialNetworks\WebServices\FacebookWs;
use App\Service\SocialNetworks\WebServices\TwitterWs;
use Astrobin\Exceptions\WsException;
use Doctrine\Common\Persistence\ObjectManager;
use Facebook\Exceptions\FacebookSDKException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PageController
 * @package App\Controller
 */
class PageController extends AbstractController
{

    /** @var DsoRepository */
    private $dsoRepository;

    /** @var TranslatorInterface */
    private $translatorInterface;

    /** @var LoggerInterface */
    private $logger;

    /**
     * PageController constructor.
     *
     * @param DsoRepository $dsoRepository
     * @param TranslatorInterface $translatorInterface
     * @param LoggerInterface $logger
     */
    public function __construct(DsoRepository $dsoRepository, TranslatorInterface $translatorInterface, LoggerInterface $logger)
    {
        $this->dsoRepository = $dsoRepository;
        $this->translatorInterface = $translatorInterface;
        $this->logger = $logger;
    }

    /**
     * @Route({
     *     "fr": "/contactez-nous",
     *     "en": "/contact-us",
     *     "de": "/kontaktiere-uns",
     *     "es": "/contactenos",
     *     "pt": "/contate-nos"
     * }, name="contact")
     *
     * @param Request $request
     * @param MailService $mailService
     *
     * @param string $receiverMail
     *
     * @return Response
     */
    public function contact(Request $request, MailService $mailService, string $receiverMail): Response
    {
        /** @var Router $router */
        $router = $this->get('router');

        $isValid = false;
        $optionsForm = [
            'method' => 'POST',
            'action' => $router->generate('contact'),
            'attr' => [
                'novalidate' => 'novalidate'
            ]
        ];

        /** @var Contact $contact */
        $contact = new Contact();
        $contactForm = $this->createForm(ContactFormType::class, $contact, $optionsForm);

        $contactForm->handleRequest($request);
        if ($contactForm->isSubmitted()) {
            if ($contactForm->isValid()) {
                /** @var Contact $contactData */
                $contactData = $contactForm->getData();

                $contactData->setLabelCountry(Countries::getName($contactData->getCountry(), $request->getLocale()));

                $templates = [
                    'html' => 'includes/emails/contact.html.twig',
                    'text' => 'includes/emails/contact.txt.twig'
                ];

                $subject = '[Contact] - ' . $this->translatorInterface->trans(Utils::listTopicsContact()[$contactData->getTopic()]);
                $content['contact'] = $contactData;

                try {
                    $mailService->sendMail($contactData->getEmail(), $receiverMail, $subject, $templates, $content);
                    $sendMail = true;
                } catch(TransportExceptionInterface $e) {
                    $this->logger->error(sprintf('Error sending mail : %s', $e->getMessage()));
                    $sendMail = false;
                }

                if (true === $sendMail) {
                    $this->addFlash('form.success','form.ok.sending');
                    $isValid = true;
                } else {
                    $this->addFlash('form.failed','form.error.sending');
                }
            } else {
                $this->addFlash('form.failed','form.error.message');
            }
        }

        $result['formContact'] = $contactForm->createView();
        $result['is_valid'] = $isValid;

        /** @var Response $response */
        $response = $this->render('pages/contact.html.twig', $result);
        $response->setSharedMaxAge(LayoutController::HTTP_TTL);
        $response->setPublic();

        return $response;
    }

    /**
     * @Route({
     *     "fr": "/mentions-legales",
     *     "en": "/legal-notice",
     *     "de": "/legal-notice",
     *     "es": "/legal-notice",
     *     "pt": "/legal-notice"
     * }, name="legal_notice")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function legalnotice(Request $request): Response
    {
        $result = [];

        /** @var RouterInterface $router */
        $router = $this->get('router');

        $result['title'] = $this->translatorInterface->trans('legal_notice.title');
        $result['first_line'] = $this->translatorInterface->trans('legal_notice.line_first', ['%dso%' => $this->translatorInterface->trans('dso')]);
        $result['second_line'] = $this->translatorInterface->trans('legal_notice.line_sec');
        $result['host'] = [
            'name' => $this->translatorInterface->trans('legal_notice.host.name'),
            'adress' => $this->translatorInterface->trans('legal_notice.host.adress'),
            'cp' => $this->translatorInterface->trans('legal_notice.host.cp'),
            'city' => $this->translatorInterface->trans('legal_notice.host.city'),
            'country' => $this->translatorInterface->trans('legal_notice.host.country')
        ];
        $result['third_line'] = $this->translatorInterface->trans('legal_notice.contact', ['%url_contact%' => $router->generate(sprintf('contact.%s', $request->getLocale())), '%label_contact%' => $this->translatorInterface->trans('contact.title')]);

        /** @var Response $response */
        $response = $this->render('pages/random.html.twig', $result);
        $response->setSharedMaxAge(LayoutController::HTTP_TTL);

        return $response;
    }


    /**
     * @Route({
     *     "fr": "/aide/api",
     *     "en": "/help/api",
     *     "es": "/help/api",
     *     "de": "/help/api",
     *     "pt": "/help/api"
     * }, name="help_api_page")
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function helpApiPage(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $isValid = false;
        /** @var ApiUser $apiUser */
        $apiUser = new ApiUser();

        $optionsForm = [
            'method' => 'POST',
            'action' => $this->get('router')->generate('help_api_page', ['_locale' => $request->getLocale()])
        ];

        /** @var FormInterface $registerApiUserForm */
        $registerApiUserForm = $this->createForm(RegisterApiUsersFormType::class, $apiUser, $optionsForm);

        $registerApiUserForm->handleRequest($request);
        if ($registerApiUserForm->isSubmitted()) {
            if ($registerApiUserForm->isValid()) {
                /** @var ObjectManager $em */
                $em = $this->getDoctrine()->getManager();

                $apiUser->setPassword(
                    $passwordEncoder->encodePassword($apiUser, $registerApiUserForm->get('rawPassword')->getData())
                );

                $em->persist($apiUser);
                $em->flush();

                $isValid = true;
                $this->addFlash('form.success', 'form.api.success');
            } else {
                $isValid = false;
                $this->addFlash('form.failed', 'form.error.message');
            }
        }

        /**  */
        $result['formRegister'] = $registerApiUserForm->createView();
        $result['is_valid'] = $isValid;

        $response = $this->render('pages/help_api.html.twig', $result);
        $response->setPublic();
        $response->setSharedMaxAge(LayoutController::HTTP_TTL);

        return $response;
    }

    /**
     * @Route({
     *     "fr": "/telechargement-donnees",
     *     "en": "/download-data",
     *     "de": "/download-data",
     *     "es": "/download-data",
     *     "pt": "/download-data",
     * }, name="download_data")
     * @param Request $request
     *
     * @return StreamedResponse
     * @throws \Exception
     */
    public function download(Request $request): StreamedResponse
    {
        $nbItems = 0;
        $data = $filters = [];

        $header = [
            'Id',
            $this->translatorInterface->trans('desigs'),
            'Name',
            $this->translatorInterface->trans('type'),
            'Constellation',
            $this->translatorInterface->trans('magnitude'),
            $this->translatorInterface->trans('ra'),
            $this->translatorInterface->trans('dec'),
            $this->translatorInterface->trans('distAl')
        ];

        // Retrieve list filters
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

        [$listDso,,] = $this->dsoRepository->setLocale($request->getLocale())->getObjectsCatalogByFilters(0, $filters, DsoRepository::MAX_SIZE);
        $data = array_map(function(Dso $dso) {
            return [
                $dso->getId(),
                implode(Dso::COMA_GLUE, array_filter($dso->getDesigs())),
                $dso->getAlt(),
                $this->translatorInterface->trans(sprintf('type.%s', $dso->getType())),
                $dso->getConstId(),
                $dso->getMag(),
                $dso->getRa(),
                $dso->getDec(),
                $dso->getDistAl()
            ];
        }, iterator_to_array($listDso));

        $data = array_merge([$header], $data);

        /** @var \DateTime $now */
        $now = new \DateTime();
        $fileName = sprintf('dso_data_%s_%s.csv', $request->getLocale(), $now->format('Ymd_His'));

        /** @var StreamedResponse $response */
        $response = new StreamedResponse(function() use ($data) {
            $handle = fopen('php://output', 'r+');
            foreach ($data as $r) {
                fputcsv($handle, $r, Utils::CSV_DELIMITER, Utils::CSV_ENCLOSURE);
            }
            fclose($handle);
        });

        $response->headers->set('content-type', 'application/force-download');
        $response->headers->set('Content-Disposition', sprintf('attachement; filename="%s"', $fileName));

        return $response;
    }

    /**
     * @Route({
     *   "en": "/skymap",
     *   "fr": "/carte-du-ciel",
     *   "es": "/skymap",
     *   "de": "/skymap",
     *   "pt": "/skymap"
     * }, name="skymap")
     */
    public function skymap(): Response
    {
        $params = [];


        /** @var Response $response */
        $response = $this->render('pages/skymap.html.twig', $params);
        $response->setSharedMaxAge(LayoutController::HTTP_TTL)->setPublic();

        return $response;
    }

    /**
     * @Route({
     *   "en": "/support-astro-otter",
     *   "fr": "/soutenir-le-site"
     * }, name="help_astro-otter")
     *
     * @param Request $request
     * @param string $paypalLink
     * @param string|null $tipeeeLink
     *
     * @return Response
     */
    public function helpAstroOtter(Request $request, ?string $paypalLink, ?string $tipeeeLink): Response
    {
        $params = [];

        $params['links'] = [
            'paypal' => [
                'label' => ucfirst('paypal'),
                'path' => $paypalLink,
                'blank' => true,
                'icon_class' => 'paypal'
            ],
            'tipeee' => [
                'label' => ucfirst('tipeee'),
                'path' => $tipeeeLink,
                'blank' => true,
                'icon_class' => 'tipeee'
            ]
        ];


        /** @var Response $response */
        $response = $this->render('pages/support.html.twig', $params);
        //$response->setPublic()->setSharedMaxAge(LayoutController::HTTP_TTL);

        return $response;
    }


    /**
     * @Route("/facebook", name="facebook_test")
     * @param FacebookWs $facebookWs
     *
     * @return Response
     * @throws FacebookSDKException
     */
    public function testFacebook(FacebookWs $facebookWs): Response
    {
        $post = $facebookWs->getPost(null);

        return new Response();
    }


    /**
     * @param TwitterWs $twitterWs
     * @param DsoManager $dsoManager
     *
     * @param RouterInterface $router
     *
     * @return Response
     * @throws WsException
     * @throws \ReflectionException
     * @Route("/twitter", name="twiiter_test")
     */
    public function testTwitter(TwitterWs $twitterWs, DsoManager $dsoManager, RouterInterface $router): Response
    {
        $id = 'm42';

        $dso = $dsoManager->buildDso($id);

        $title = $dsoManager->buildTitle($dso);
        $url = $dsoManager->getDsoUrl($dso, Router::ABSOLUTE_URL);
        $image = null; //$dso->getImage();

        $tweet = $twitterWs->postLink($title, $url, $image);

        /** @var Response $response */
        $response = new Response();
        return $response;
    }


    /**
     * @Route("/notindexed", name="not_indexed")
     * @param DsoRepository $dsoRepository
     *
     * @return Response
     * @throws \ReflectionException
     */
    public function tosearch(DsoRepository $dsoRepository): Response
    {
        $data = [];
        $limit = 313;
        $i = 1;

        $fullArray = array_map(function($i) {
            return sprintf('Sh2-%d', $i);
        }, range(1, $limit, $i));


        $results = $dsoRepository->getObjectsCatalogByFilters(0,['catalog' => 'sh'], 1000);
        /** @var Dso $dso */
        foreach ($results[0] as $dso) {
            if (0 === strpos(strtolower($dso->getId()), 'sh')) {
                array_push($data, $dso->getId());
            } else {
                $item = preg_grep('/^Sh2-\d*/', $dso->getDesigs());
                if (false !== reset($item)) {
                    array_push($data, reset($item));
                }
            }
        }

        usort($data, function($a, $b) {
            [, $nA] = explode('-', $a);
            [, $nB] = explode('-', $b);

            return $nA > $nB;
        });


        $notIndexedItems = array_diff($fullArray, $data);

        return new Response(print_r(array_values($notIndexedItems)));
    }
}
