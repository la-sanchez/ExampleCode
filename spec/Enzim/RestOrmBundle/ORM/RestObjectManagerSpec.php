<?php

namespace spec\Enzim\RestOrmBundle\ORM;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class RestObjectManagerSpec extends ObjectBehavior
{

    function let()
    {
        $this->beConstructedWith('http://a.fake.url', 'spec\Enzim\RestOrmBundle\Serializer');
    }


    function it_is_initializable()
    {
        $this->shouldHaveType('Enzim\RestOrmBundle\ORM\RestObjectManager');
        $this->getEntrypointUri()->shouldBe('http://a.fake.url');
        $this->getNamespace()->shouldBe('spec\Enzim\RestOrmBundle\Serializer');
    }

}
