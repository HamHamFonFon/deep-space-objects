<?php

namespace App\Repository;

use App\Entity\AbstractEntity;
use App\Entity\Constellation;
use App\Entity\Dso;
use App\Entity\Observation;
use Elastica\Client;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;
use Negotiation\Match;

/**
 * Class AbstractRepository
 * @package App\Repository
 */
abstract class AbstractRepository
{
    protected $locale;

    /** @var Search  */
    protected $client;

    const FROM = 0;
    const SIZE = 20;
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    /**
     * AbstractRepository constructor.
     * @param Client $client
     * @param $locale
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        if (is_null($this->locale)) {
            $this->locale = 'en';
        }
        return $this->locale;
    }

    /**
     * @param $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Return document by Id
     *
     * @param $id
     * @return ResultSet
     */
    protected function findById($id): ResultSet
    {
        /** @var Constellation|Observation|Dso|AbstractEntity $entity */
        $entityName = $this->getEntity();
        $entity = new $entityName;

        $this->client->getIndex($entity::getIndex());

        /** @var Query\Term $term */
        $matchQuery = new Query\Match();
        $matchQuery->setField('id', $id);

        /** @var Search $search */
        $search = new Search($this->client);

        /** @var ResultSet $resultSet */
        return $search->addIndex($this->getType())->search($matchQuery);
    }


    /**
     * @param $searchTerm
     * @param $listSearchFields
     * @return ResultSet
     */
    public function requestBySearchTerms($searchTerm, $listSearchFields)
    {
        $this->client->getIndex($this->getType());

        /** @var Query\MultiMatch $query */
        $query = new Query\MultiMatch();
        $query->setFields($listSearchFields);
        $query->setQuery($searchTerm);
        $query->setType('phrase_prefix');

//        if (ObservationRepository::INDEX_NAME == $this->getType()) {
//            /** @var Query\Match $queryMatch */
//            $queryMatch = new Query\Match();
//            $queryMatch->setField('is_public', true);
//
//            $query->setQuery($queryMatch);
//        }

        /** @var Search $search */
        $search = new Search($this->client);
        $search->addIndex($this->getType());

        return $search->search($query);
    }

    abstract protected function getEntity();

    abstract protected function getType();
}
