<?php

namespace App\Controller;

use App\Classes\Utils;
use App\Entity\Contact;
use App\Entity\Dso;
use App\Forms\ContactFormType;
use App\Helpers\MailHelper;
use App\Repository\DsoRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
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

    /**
     * PageController constructor.
     *
     * @param DsoRepository $dsoRepository
     * @param TranslatorInterface $translatorInterface
     */
    public function __construct(DsoRepository $dsoRepository, TranslatorInterface $translatorInterface)
    {
        $this->dsoRepository = $dsoRepository;
        $this->translatorInterface = $translatorInterface;
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
     * @param MailHelper $mailHelper
     *
     * @return Response
     * @throws \Exception
     */
    public function contact(Request $request, MailHelper $mailHelper)
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

                $contactData->label_country = Intl::getRegionBundle()->getCountryNames()[$contactData->getCountry()];

                $template = [
                    'html' => 'includes/emails/contact.html.twig',
                    'text' => 'includes/emails/contact.txt.twig'
                ];

                $subject = $this->translatorInterface->trans(Utils::listTopicsContact()[$contactData->getTopic()]);
                $content['contact'] = $contactData;

                $sendMail = $mailHelper->sendMail($contactData->getEmail(), $this->getParameter('app.notifications.email_sender'), $subject, $template, $content);
                if (1 === $sendMail) {
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
        $response->setSharedMaxAge(3600);
        $response->setPublic();

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
    public function download(Request $request)
    {
        $nbItems = 0;
        $data = $filters = $listAggregations = [];

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

        list($listDso, $aggregates, $nbItems) = $this->dsoRepository->setLocale($request->getLocale())->getObjectsCatalogByFilters(0, $filters, DsoRepository::MAX_SIZE);
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
}
