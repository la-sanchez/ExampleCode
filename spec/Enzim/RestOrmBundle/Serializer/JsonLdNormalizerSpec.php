<?php

namespace spec\Enzim\RestOrmBundle\Serializer;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Enzim\RestOrmBundle\ORM\ResourceLoader;
use Enzim\RestOrmBundle\ORM\RestObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class JsonLdNormalizerSpec extends ObjectBehavior
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
        $loader->loadByPath('/api/bar/306')->willReturn(new Bar('306'));
        $loader->loadByPath('/api/bar/307')->willReturn(new Bar('307'));

        $objectManager->getNamespace()->willReturn('spec\Enzim\RestOrmBundle\Serializer');
        $objectManager->getClassMetadata('spec\Enzim\RestOrmBundle\Serializer\Foo')->willReturn($metadata);
        $objectManager->getClassMetadata('spec\Enzim\RestOrmBundle\Serializer\Bar')->willReturn( new ClassMetadataInfo('Bar') );
        $objectManager->getLoader('spec\Enzim\RestOrmBundle\Serializer\Bar')->willReturn($loader);

        $this->beConstructedWith($objectManager);
    }


    function it_is_initializable()
    {
        $this->shouldHaveType('Enzim\RestOrmBundle\Serializer\JsonLdNormalizer');
    }


    function it_should_normalize_an_object()
    {
        $foo = new Foo();
        $foo->id = 33;
        $foo->code = 'XX';
        $foo->name = 'xyz';

        $array = $this->normalize($foo, 'json');

        $array['id']->shouldBeLike(33);
        $array['code']->shouldBe('XX');
        $array['name']->shouldBe('xyz');
    }


    function it_should_denormalize_an_object()
    {
        $foo = $this->denormalize( array(
            "@id" => "/api/foo/83",
            "@type" => "Foo",
            "code" => "30",
            "name" => "abcde",
            "bar" => "/api/bar/306"
        ), null );

        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->id->shouldBeLike(83);
        $foo->code->shouldBe('30');
        $foo->name->shouldBe('abcde');
        $foo->bar->shouldBeAnInstanceOf('ProxyManager\Proxy\ProxyInterface');
        $foo->bar->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Bar');
        $foo->bar->id->shouldBe('306');
    }


    function it_should_denormalize_an_object_with_an_embedded_object()
    {
        $foo = $this->denormalize( array(
            "@id" => "/api/foo/83",
            "@type" => "Foo",
            "code" => "30",
            "name" => "abcde",
            "bar" =>  array(
                "@id" => "/api/bar/306",
                "@type" => "Bar",
            )
        ), null );

        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->id->shouldBeLike(83);
        $foo->code->shouldBe('30');
        $foo->name->shouldBe('abcde');
        $foo->bar->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Bar');
        $foo->bar->id->shouldBe('306');
    }



    function it_should_denormalize_an_object_with_a_collection_of_embedded_objects()
    {
        $foo = $this->denormalize( array(
            "@id" => "/api/foo/83",
            "@type" => "Foo",
            "bars" => array(
                array(
                    "@id" => "/api/bar/306",
                    "@type" => "Bar",
                ),
                array(
                    "@id" => "/api/bar/307",
                    "@type" => "Bar",
                ),
            )
        ), null );

        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->bars->shouldHaveCount(2);
        $foo->bars[0]->id->shouldBeLike(306);
        $foo->bars[1]->id->shouldBeLike(307);
    }


    function it_should_denormalize_an_object_with_a_collection_of_references()
    {
        $foo = $this->denormalize( array(
            "@id" => "/api/foo/83",
            "@type" => "Foo",
            "bars" => array(
                "/api/bar/306",
                "/api/bar/307"
            )
        ), null );

        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->bars->shouldHaveCount(2);
        $foo->bars[0]->shouldBeAnInstanceOf('ProxyManager\Proxy\ProxyInterface');
        $foo->bars[1]->shouldBeAnInstanceOf('ProxyManager\Proxy\ProxyInterface');
        $foo->bars[0]->id->shouldBeLike(306);
        $foo->bars[1]->id->shouldBeLike(307);
    }


    function it_should_denormalize_a_hydra_empty_collection()
    {
        $collection = $this->denormalize( array(
            "@id" => "/api/postcodes?code=16878",
            "@type" => "hydra:PagedCollection",
            "hydra:totalItems" => 0,
            "hydra:itemsPerPage" => 10,
            "hydra:firstPage" => "/api/postcodes?code=16878",
            "hydra:lastPage" => "/api/postcodes?code=16878",
            "hydra:member" => array()
        ), null );

        $collection->shouldBeAnInstanceOf('Enzim\RestOrmBundle\Hydra\Collection');
        $collection->getTotalItems()->shouldBe(0);
        $collection->getItemsPerPage()->shouldBe(10);
        $collection->getMember()->shouldBeArray();
    }


    function it_should_denormalize_items_in_a_hydra_collection()
    {
        $collection = $this->denormalize( array(
            "@id" => "/api/foo",
            "@type" => "hydra:PagedCollection",
            "hydra:totalItems" => 1,
            "hydra:itemsPerPage" => 10,
            "hydra:firstPage" => "/api/foo",
            "hydra:lastPage" => "/api/foo",
            "hydra:member" => array(
                array(
                    "@id" => "/api/foo/83",
                    "@type" => "Foo",
                    "code" => "30",
                    "name" => "abcde",
                    "bar" => "/api/bar/306"
                )
            )
        ), null );

        $collection->shouldBeAnInstanceOf('Enzim\RestOrmBundle\Hydra\Collection');
        $foo = $collection->getMember()[0];
        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
        $foo->id->shouldBeLike(83);
        $foo->bar->shouldBeAnInstanceOf('ProxyManager\Proxy\ProxyInterface');
    }


    function it_should_not_allow_malformed_objects()
    {
        $this->shouldThrow('\RuntimeException')->duringDenormalize( array(
            "id" => "/api/foo/83",
        ), null );
    }


    function it_should_denormalize_an_error()
    {
        $error = $this->denormalize( array(
            "@type" => "Error",
            "hydra:title" => "An error occurred",
            "hydra:description" => "Foo error",
            "trace" => array(
                array(
                    "file" => "Foo",
                    "line" => 200
                )
            )
        ), null);

        $error->shouldBeAnInstanceOf('Enzim\RestOrmBundle\Hydra\Error');
        $error->getTitle()->shouldBe('An error occurred');
        $error->getDescription()->shouldBe('Foo error');
        $error->getTrace()->shouldHaveCount(1);
        $error->getTrace()[0]['file']->shouldBe('Foo');
        $error->getTrace()[0]['line']->shouldBe(200);
    }

}


class Foo
{
    public $id;
    public $code;
    public $name;
    public $bar;
    public $bars;
}


class Bar
{
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }
}