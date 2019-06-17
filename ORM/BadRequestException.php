<?php

namespace Enzim\RestOrmBundle\ORM;

/**
 * @deprecated
 */
class BadRequestException extends \RuntimeException
{
    private $responseObject;


    public function __construct($responseObject, $message = '')
    {
        parent::__construct($message, 400, null);
        $this->responseObject = $responseObject;
    }


    /**
     * @return string
     */
    public function getResponseObject()
    {
        return $this->responseObject;
    }

}