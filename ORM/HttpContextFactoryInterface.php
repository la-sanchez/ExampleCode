<?php

namespace Enzim\RestOrmBundle\ORM;

use \SplFileInfo;


interface HttpContextFactoryInterface
{

    /**
     * @param string $url
     * @param string $method
     * @param string|SplFileInfo $content
     * @return array
     */
    function create($url, $method, $content);

}