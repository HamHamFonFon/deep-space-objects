<?php

namespace App\Entity\ES;

use App\Repository\ConstellationRepository;

/**
 * Class Constellation
 * @package App\Entity
 */
class Constellation
{
    /** @var  */
    private $id;

    /** @var  */
    private $gen;

    /** @var  */
    private $alt;

    /** @var  */
    private $description;

    /** @var  */
    private $rank;

    /** @var  */
    private $loc;

    /** @var  */
    private $geometry;

    /** @var  */
    private $geometryLine;

    /** @var  */
    private $listDso;

    /** @var  */
    private $fullUrl;

    /** @var  */
    private $map;

    /** @var  */
    private $image;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getGen()
    {
        return $this->gen;
    }

    /**
     * @param mixed $gen
     */
    public function setGen($gen): void
    {
        $this->gen = $gen;
    }

    /**
     * @return mixed
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * @param mixed $alt
     *
     * @return Constellation
     */
    public function setAlt($alt): self
    {
        if (!$this->locale || 'en' === $this->locale) {
            $this->alt = $alt['alt'];
        } else {
            $this->alt = $alt[sprintf('alt_%s', $this->locale)];
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     *
     * @return Constellation
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        if (!$this->locale || 'en' === $this->locale) {
            $this->description = $description['description'];
        } else {
            $this->description = $description[sprintf('description_%s', $this->locale)];
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param mixed $rank
     */
    public function setRank($rank): void
    {
        $this->rank = $rank;
    }

    /**
     * @return mixed
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * @param mixed $loc
     */
    public function setLoc($loc): void
    {
        $this->loc = $loc;
    }

    /**
     * @return mixed
     */
    public function getGeometry()
    {
        return $this->geometry;
    }

    /**
     * @param mixed $geometry
     */
    public function setGeometry($geometry): void
    {
        $this->geometry = $geometry;
    }

    /**
     * @return mixed
     */
    public function getGeometryLine()
    {
        return $this->geometryLine;
    }

    /**
     * @param mixed $geometryLine
     */
    public function setGeometryLine($geometryLine): void
    {
        $this->geometryLine = $geometryLine;
    }


    /**
     * @return mixed
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param mixed $map
     */
    public function setMap($map): void
    {
        $this->map = $map;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

}
