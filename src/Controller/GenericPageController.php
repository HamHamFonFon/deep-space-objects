<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class GenericPageController extends AbstractController
{

    /**
     * @Route({
     *   "en": "/skymap",
     *   "fr": "/carte-du-ciel",
     *   "es": "/skymap",
     *   "de": "/skymap",
     *   "pt": "/skymap"
     * }, name="skymap")
     */
    public function skymap()
    {
        /** @var Response $response */
        $response = new Response();

        return $response;
    }


    /**
     * @Route({
     *  "en": "/news",
     *  "fr": "/actualites",
     *  "es": "/news",
     *  "de": "/news",
     *  "pt": "/news"
     * }, name="news")
     */
    public function blog()
    {
       $response = new Response();

       return $response;
    }
}