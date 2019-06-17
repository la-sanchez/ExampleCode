<?php

namespace Enzim\RestOrmBundle\ORM;

use AppBundle\Controller\Rest\ApiRequestHelper;
use Symfony\Component\Serializer\Serializer;

class ResourceLoader
{

    const PAGE_SIZE = 30;

    /** @var ApiRequestHelper */
    private $apiRequestHelper;

    /** @var string */
    private $baseUri;

    /** @var string */
    private $resourceUri;

    /** @var Serializer */
    private $serializer;

    /**
     * @param ApiRequestHelper             $apiRequestHelper
     * @param string                      $baseUri
     * @param string                      $resourceUri
     * @param Serializer                  $serializer
     */
    function __construct($apiRequestHelper, $baseUri, $resourceUri, $serializer)
    {
        $this->apiRequestHelper = $apiRequestHelper;
        $this->baseUri = $baseUri;
        $this->resourceUri = $resourceUri;
        $this->serializer = $serializer;
    }

    public function loadById($identifier)
    {
        $uri = $this->baseUri . $this->resourceUri;
        if ($identifier !== null) {
            $uri .= '/' . $identifier;
        }

        return $this->sendHttpRequest($uri);
    }

    public function loadByPath($path)
    {
        return $this->sendHttpRequest($this->baseUri . $path);
    }

    public function load(array $criteria, $orderBy, $offset)
    {
        if (is_array($orderBy)) {
            foreach ($orderBy as $property => $ascOrDesc) {
                $criteria["order[$property]"] = $ascOrDesc;
            }
        }

        if ($offset !== null) {
            $page = (int)($offset / self::PAGE_SIZE) + 1;
            if ($page > 1) {
                $criteria['page'] = $page;
            }
        }

        return $this->sendHttpRequest($this->baseUri . $this->resourceUri . '?' . http_build_query($criteria));
    }

    public function save($identifier, $object)
    {
        $uri = $this->baseUri . $this->resourceUri;
        if ($identifier !== null) {
            return $this->sendHttpRequest($uri . '/' . $identifier, 'PUT', $object);
        }

        return $this->sendHttpRequest($uri, 'POST', $object);
    }

    //--------------------------------------------------------------------------------

    protected function sendHttpRequest($uri, $method = 'GET', $object = null)
    {
        try {
            $content = $object ? $this->serializer->serialize($object, 'json') : null;
            $response = $this->apiRequestHelper->makeApiRequest($uri, $method, $content, false);
            if ($response !== false && !empty($response) && isset($response)) {
                return $this->serializer->deserialize($response->getBody(), null, 'json');
            }
        } catch (RestError $e) {

        }

        return null;
    }
}
