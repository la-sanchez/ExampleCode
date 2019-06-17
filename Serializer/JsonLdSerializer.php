<?php

namespace Enzim\RestOrmBundle\Serializer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;


class JsonLdSerializer extends Serializer
{

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct($objectManager)
    {
        parent::__construct(
            [new JsonLdNormalizer($objectManager)],
            [new JsonEncoder()]
        );
    }

}