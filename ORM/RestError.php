<?php

namespace Enzim\RestOrmBundle\ORM;


class RestError extends \Exception
{
    protected $responseBody;


    public function __construct($code, $responseBody)
    {
        parent::__construct("", $code);
        $this->responseBody = $responseBody;
    }


    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

}