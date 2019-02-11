<?php

namespace App\Managers;

use App\Entity\Constellation;
use App\Helpers\UrlGenerateHelper;
use App\Repository\ConstellationRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ConstellationManager
 * @package App\Managers
 */
class ConstellationManager
{
    /** @var ConstellationRepository  */
    private $constellationRepository;
    /** @var UrlGenerateHelper  */
    private $urlGeneratorHelper;
    /** @var TranslatorInterface  */
    private $translatorInterface;

    /**
     * ConstellationManager constructor.
     * @param $constellationRepository
     * @param $urlGeneratorHelper
     * @param $translatorInterface
     */
    public function __construct(ConstellationRepository $constellationRepository, UrlGenerateHelper $urlGeneratorHelper, TranslatorInterface $translatorInterface)
    {
        $this->constellationRepository = $constellationRepository;
        $this->urlGeneratorHelper = $urlGeneratorHelper;
        $this->translatorInterface = $translatorInterface;
    }


    /**
     * Build a constellation entoty from ElasticSearch request by $id
     * @param $id
     * @return Constellation
     * @throws \ReflectionException
     */
    public function buildConstellation($id): Constellation
    {
        /** @var Constellation $constellation */
        $constellation = $this->constellationRepository->getObjectById($id);
        $constellation->setFullUrl($this->urlGeneratorHelper->generateUrl($constellation));

        return $constellation;
    }


    /**
     * Get all constellation and build formated data for template
     * @throws \ReflectionException
     */
    public function buildListConstellation()
    {
        $listConstellation = $this->constellationRepository->getAllConstellation();

        return array_map(function(Constellation $constellation) {
            return [
                'id' => $constellation->getId(),
                'value' => $constellation->getAlt(),
                'label' => $constellation->getGen(),
                'url' => $this->buildUrl($constellation),
                'image' => $constellation->getImage()
            ];
        }, iterator_to_array($listConstellation->getIterator()));
    }

    /**
     * @param $constellation
     * @return string
     */
    private function buildUrl($constellation)
    {
        return $this->urlGeneratorHelper->generateUrl($constellation);
    }
}