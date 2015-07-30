<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Unit\Utils\Health;

use Trismegiste\SocialBundle\Utils\Health\MongoStatus;

/**
 * MongoStatusTest tests MongoStatus
 */
class MongoStatusTest extends \PHPUnit_Framework_TestCase
{

    /** @var MongoStatus */
    protected $sut;
    protected $collection;

    protected function setUp()
    {
        $this->collection = $this->getMockBuilder('MongoCollection')
                ->disableOriginalConstructor()
                ->getMock();
        $this->sut = new MongoStatus($this->collection);
    }

    public function testGetCounterPerAlias()
    {
        $this->collection->expects($this->once())->method('aggregateCursor');
        $this->sut->getCounterPerAlias();
    }

}