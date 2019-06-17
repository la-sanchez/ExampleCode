<?php

namespace spec\Enzim\RestOrmBundle\ORM;

use Enzim\RestOrmBundle\Hydra\Collection;
use Enzim\RestOrmBundle\ORM\ResourceLoader;
use Enzim\RestOrmBundle\ORM\RestObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class RestCollectionSpec extends ObjectBehavior
{

    function let(ResourceLoader $loader)
    {
        $firstPage = Collection::create( array('a', 'b', 'c'), 4, 3, 'prev', 'next', 'first', 'last' );
        $secondPage = Collection::create( array('d'), 4, 1, 'prev', 'next', 'first', 'last' );

        $loader->loadByPath('next')->willReturn($secondPage);
        $this->beConstructedWith($loader, $firstPage);
    }


    function it_is_initializable()
    {
        $this->shouldHaveType('Enzim\RestOrmBundle\ORM\RestCollection');
        $this->shouldHaveType('\Iterator');
    }


    function it_should_be_ready_after_initialization()
    {
        $this->current()->shouldBe('a');
        $this->key()->shouldBe(0);
        $this->valid()->shouldBe(true);
        $this->shouldHaveCount(4);
    }


    function it_should_be_iterable()
    {
        $this->current()->shouldBe('a');
        $this->next();
        $this->current()->shouldBe('b');
        $this->next();
        $this->current()->shouldBe('c');
        $this->next();
        $this->current()->shouldBe('d');
    }


    function it_should_be_rewindable()
    {
        $this->next();
        $this->current()->shouldBe('b');
        $this->rewind();
        $this->current()->shouldBe('a');
    }


}
