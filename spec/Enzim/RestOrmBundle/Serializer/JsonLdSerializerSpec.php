<?php

namespace spec\Enzim\RestOrmBundle\Serializer;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Enzim\RestOrmBundle\ORM\ResourceLoader;
use Enzim\RestOrmBundle\ORM\RestObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class JsonLdSerializerSpec extends ObjectBehavior
{

    function let(RestObjectManager $objectManager, ClassMetadataInfo $metadata, ResourceLoader $loader)
    {
        $metadata->beADoubleOf('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $metadata->isSingleValuedAssociation('bar')->willReturn(true);
        $metadata->getAssociationTargetClass('bar')->willReturn('spec\Enzim\RestOrmBundle\Serializer\Bar');
        $metadata->isSingleValuedAssociation(Argument::not('bar'))->willReturn(false);
        $metadata->isCollectionValuedAssociation('bars')->willReturn(true);
        $metadata->getAssociationTargetClass('bars')->willReturn('spec\Enzim\RestOrmBundle\Serializer\Bar');
        $metadata->isCollectionValuedAssociation(Argument::not('bars'))->willReturn(false);

        $loader->beADoubleOf('Enzim\RestOrmBundle\ORM\ResourceLoader');
        $loader->loadByPath('/api/bar/306')->willReturn(new Bar('y'));

        $objectManager->getNamespace()->willReturn('spec\Enzim\RestOrmBundle\Serializer');
        $objectManager->getClassMetadata('spec\Enzim\RestOrmBundle\Serializer\Foo')->willReturn($metadata);
        $objectManager->getLoader('spec\Enzim\RestOrmBundle\Serializer\Bar')->willReturn($loader);

        $this->beConstructedWith($objectManager);
    }


    function it_is_initializable()
    {
        $this->shouldHaveType('Enzim\RestOrmBundle\Serializer\JsonLdSerializer');
    }


    function it_should_serialize_an_object()
    {
        $foo = new Foo();
        $foo->id = 33;
        $foo->code = 'XX';
        $foo->name = 'xyz';

        $json = $this->serialize($foo, 'json');

        $json->shouldContain('"id":33');
        $json->shouldContain('"code":"XX"');
        $json->shouldContain('"name":"xyz"');
        $json->shouldContain('"bar":null');
    }


    function it_should_deserialize_a_json_object()
    {
        $foo = $this->deserialize('{
            "@id" : "/api/foo/82",
            "@type" : "Foo",
            "code" : "29",
            "name" : "lala",
            "bar" : "/api/bar/306"
        }', null, 'json');

        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->id->shouldBeLike(82);
        $foo->code->shouldBe('29');
        $foo->name->shouldBe('lala');
        $foo->bar->shouldBeAnInstanceOf('ProxyManager\Proxy\ProxyInterface');
        $foo->bar->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Bar');
    }


    function it_should_deserialize_a_constraint_violation_list()
    {
        $list = $this->deserialize('{
            "@context" : "/api/contexts/ConstraintViolationList",
            "@type" : "ConstraintViolationList",
            "hydra:title" : "An error occurred",
            "hydra:description" : "nombre: This value should not be blank.\napellidos: This value should not be blank.\n",
            "violations" : [ {
                "propertyPath" : "nombre",
                "message" : "This value should not be blank."
            }, {
                "propertyPath" : "apellidos",
                "message" : "This value should not be blank."
            } ]
        }', null, 'json');

        $list->shouldBeAnInstanceOf('Enzim\RestOrmBundle\Hydra\ConstraintViolationList');
        $list->getTitle()->shouldBe('An error occurred');
        $list->getDescription()->shouldBe("nombre: This value should not be blank.\napellidos: This value should not be blank.\n");
        $list->getViolations()->shouldHaveCount(2);
        $list->getViolations()[0]['propertyPath']->shouldBe('nombre');
        $list->getViolations()[0]['message']->shouldBe('This value should not be blank.');
    }

}
