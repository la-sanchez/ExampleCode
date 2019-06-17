<?php

namespace Enzim\RestOrmBundle\Hydra;


class ConstraintViolationList 
{
    /** @var string */
    private $title;

    /** @var string */
    private $description;

    /** @var array */
    private $violations;


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->violations;
    }

}