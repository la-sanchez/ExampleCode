<?php

namespace Enzim\RestOrmBundle\Hydra;


class Error 
{
    /** @var string */
    private $title;

    /** @var string */
    private $description;

    /** @var array */
    private $trace;


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
    public function getTrace()
    {
        return $this->trace;
    }

}