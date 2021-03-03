<?php
/*
namespace App\Managers;

use App\Service\Cache\CachePoolInterface;
use App\DataTransformer\ObservationDataTransformer;
use App\Entity\ES\ListDso;
use App\Entity\ES\Observation;
use App\Helpers\UrlGenerateHelper;
use App\Repository\ObservationRepository;
use Elastica\Exception\ElasticsearchException;
use Elastica\Exception\NotFoundException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated
 * Class ObservationManager
 *
 * @package App\Managers
 */
/*class ObservationManager
{
    use ManagerTrait;

    private ObservationRepository $observationRepository;
    private UrlGenerateHelper $urlGeneratorHelper;
    private CachePoolInterface $cacheUtil;
    private string $locale;
    private DsoManager $dsoManager;
    private TranslatorInterface $translator;
    private ObservationDataTransformer $obsDataTransformer;

    /**
     * ObservationManager constructor.
     *
     * @param ObservationRepository $observationRepository
     * @param UrlGenerateHelper $urlGeneratorHelper
     * @param CachePoolInterface $cacheUtil
     * @param $locale
     * @param DsoManager $dsoManager
     * @param TranslatorInterface $translator
     * @param ObservationDataTransformer $obsDataTransformer
     */
    /*public function __construct(ObservationRepository $observationRepository, UrlGenerateHelper $urlGeneratorHelper, CachePoolInterface $cacheUtil, $locale, DsoManager $dsoManager, TranslatorInterface $translator, ObservationDataTransformer $obsDataTransformer)
    {
        $this->observationRepository = $observationRepository;
        $this->urlGeneratorHelper = $urlGeneratorHelper;
        $this->cacheUtil = $cacheUtil;
        $this->locale = $locale;
        $this->dsoManager = $dsoManager;
        $this->translator = $translator;
        $this->obsDataTransformer = $obsDataTransformer;
    }

    /**
     * @param $id
     *
     * @return Observation
     * @throws ReflectionException
     */
    /*public function buildObservation($id): Observation
    {
        /** @var Observation $observation
        $observation = $this->observationRepository->setLocale($this->locale)->getObservationById($id);
        if (is_null($observation)) {
            throw new NotFoundException();
        }

        if ($observation instanceof Observation) {
            $observation->setFullUrl($this->urlGeneratorHelper->generateUrl($observation));
            /** @var ListDso $dsoList
            $dsoList = new ListDso();

            if (!is_null($observation->getDsoList()) && 0 < count($observation->getDsoList())) {

                $listIdDso = array_values($observation->getDsoList());
                array_walk($listIdDso, function($id) use ($dsoList) {
//                    $dso = $this->dsoManager->buildDso($id);
                    $dsoList->addDso($dso);
                });
            }

            $observation->setDsoList($dsoList);
        }

        return $observation;
    }

    /**
     * @param Observation $observation
     *
     * @return array

    public function formatVueData(Observation $observation): array
    {
        $observationArray = $this->obsDataTransformer->toArray($observation);
        return $this->formatEntityData($observationArray, [], $this->translator);
    }


    /***
     * Format result search observation
     * @param $terms
     *
     * @return mixed

    public function buildSearchObservationByTerms($terms)
    {
        $listObservation = $this->observationRepository->setLocale($this->locale)->getObservationsBySearchTerms($terms);

        return call_user_func("array_merge", array_map(function (Observation $observation) {
            return [
                'id' => $observation->getId(),
                'ajaxValue' => $observation->getName(),
                'label' => $observation->getUsername(),
                'url' => $this->urlGeneratorHelper->generateUrl($observation),
                'type' => ObservationRepository::INDEX_NAME
            ];
        }, $listObservation));
    }


    /**
     * @return array
     * @throws ReflectionException

    public function getAllObservation()
    {
        /** @var UrlGenerateHelper $urlGenerator
        $urlGenerator = $this->urlGeneratorHelper;

        return array_map(function(Observation $observation) use($urlGenerator) {
            $observation->setObservationDate($observation->getObservationDate(), false);
            // Get fomat date from locale
            $formatter = \IntlDateFormatter::create(
                $this->locale,
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                \IntlDateFormatter::GREGORIAN,
                ''
            );

            return [
                'type' => 'Feature',
                'properties' => [
                    'name' => $observation->getName(),
                    'username' => $observation->getUsername(),
                    'full_url' => $urlGenerator->generateUrl($observation),
                    'date' => $formatter->format($observation->getObservationDate()),
                    'layer' => 'l_dsoplanner'
                ],
                'geometry' => $observation->getLocation()
            ];
        }, iterator_to_array($this->observationRepository->getAllObservation()));
    }

    /**
     * Format data : Entity into Array with CamelCase properties into snake_case keys
     * @param Observation $observation
     *
     * @return true|string
     * @throws ExceptionInterface

    public function addObservation(Observation $observation)
    {
        /** @var ObjectNormalizer $normalizer
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        /** @var Serializer $serialize
        $serialize = new Serializer([$normalizer]);

        $observationData = $serialize->normalize($observation, null, ['attributes' => $observation->getFieldsObjectToJson()]);

        try {
            $response = $this->observationRepository->add($observationData, $observation->getId());
            if (Response::HTTP_CREATED === $response->getStatus()) {
                return true;
            } else {
                return $response->getErrorMessage();
            }
        } catch (ElasticsearchException $e) {
            return $e->getMessage();
        }
    }
}
