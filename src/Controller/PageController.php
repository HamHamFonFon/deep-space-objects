<?php

namespace App\Controller;

use App\Classes\Utils;
use App\Entity\Contact;
use App\Forms\ContactFormType;
use App\Helpers\MailHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;

/**
 * Class PageController
 * @package App\Controller
 */
class PageController extends AbstractController
{

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
        $isValid = null;
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

                $contactData->label_country = Intl::getRegionBundle()->getCountryNames($contactData->getCountry());

                $template = [
                    'html' => 'includes/emails/contact.html.twig',
                    'text' => 'includes/emails/contact.txt.twig'
                ];

                $subject = Utils::listTopicsContact()[$contactData->getTopic()];
                $content['contact'] = $contactData;
                $sendMail = $mailHelper->sendMail($contactData->getEmail(), $this->getParameter('app.notifications.email_sender'), $subject, $template, $content);
                if (true === $sendMail) {
                    $isValid = true;
                } else {
                    $isValid = false;
                }
            } else {
                $isValid = false;
            }
        }

        $result['formContact'] = $contactForm->createView();
        $result['is_valid'] = $isValid;

        /** @var Response $response */
        $response = $this->render('pages/contact.html.twig', $result);
        $response->setSharedMaxAge(3600);

        return $response;
    }

}
