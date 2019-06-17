<?php

namespace spec\Enzim\RestOrmBundle\ORM;

use Enzim\RestOrmBundle\Hydra\Collection;
use Enzim\RestOrmBundle\ORM\ResourceLoader;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\Enzim\RestOrmBundle\Serializer\Foo;


class RestObjectRepositorySpec extends ObjectBehavior
{

    function let(ResourceLoader $loader)
    {
        $this->beConstructedWith($loader, 'Foo');
    }


    function it_is_initializable()
    {
        $this->shouldHaveType('Enzim\RestOrmBundle\ORM\RestObjectRepository');
    }


    function it_should_have_class_name()
    {
        $this->getClassName()->shouldBe('Foo');
    }


    function it_finds_an_object_by_id(ResourceLoader $loader)
    {
        $loader->loadById('123')->willReturn( new Foo() );
        $loader->loadById('123')->shouldBeCalled();

        $foo = $this->find('123');
        $foo->shouldBeAnInstanceOf('spec\Enzim\RestOrmBundle\Serializer\Foo');
    }


    function it_finds_all_objects_in_repository(ResourceLoader $loader)
    {
        $loader->loadById(null)->willReturn( new Collection() );
        $loader->loadById(null)->shouldBeCalled();

        $this->findAll();
    }

}