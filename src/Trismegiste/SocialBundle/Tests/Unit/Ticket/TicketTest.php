<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Unit\Ticket;

use Trismegiste\SocialBundle\Ticket\Ticket;

/**
 * TicketTest tests the Ticket entity
 */
class TicketTest extends \PHPUnit_Framework_TestCase
{

    /** @var Ticket */
    protected $sut;

    /** @var PurchaseChoice */
    protected $choice;

    protected function setUp()
    {
        $this->choice = $this->getMockForAbstractClass('Trismegiste\SocialBundle\Ticket\PurchaseChoice');
        $this->choice->setDurationValue(5);
        $this->choice->expects($this->any())
                ->method('getDurationUnit')
                ->will($this->returnValue('day'));
        $this->sut = new Ticket($this->choice);
    }

    public function testPurchaseDate()
    {
        $this->assertInstanceOf('DateTime', $this->sut->getPurchasedAt());
    }

    public function testWithPurchaseDateConstruct()
    {
        $past = new \DateTime();
        $past->modify('-3 days');
        $t = new Ticket($this->choice, $past);
        $this->assertTrue($t->isValid());
    }

    public function testNotExpired()
    {
        $this->assertTrue($this->sut->isValid());
    }

    public function testNotExpiredFutur()
    {
        $now = new \DateTime();
        $now->modify("+1 day"); // we test for tomorrow
        $this->assertTrue($this->sut->isValid($now));
    }

    public function testExpired()
    {
        $now = new \DateTime();
        $now->modify("+1 month"); // we test in 1 month
        $this->assertFalse($this->sut->isValid($now));
    }

    public function testExpirationDate()
    {
        $this->assertInstanceOf('\DateTime', $this->sut->getExpiredAt());
        $this->assertNotEquals($this->sut->getExpiredAt(), $this->sut->getPurchasedAt());
    }

    public function testGetTitle()
    {
        $this->choice->expects($this->once())
                ->method('getTitle');
        $this->sut->getTitle();
    }

    public function testSetExpiredAt()
    {
        $expiration = new \DateTime('2019-01-01');
        $this->sut->setExpiredAt($expiration);
        $this->assertEquals($expiration, $this->sut->getExpiredAt());
    }

    public function testTransactionInfo()
    {
        $this->assertEquals([], $this->sut->getTransactionInfo());
        $this->sut->setTransactionInfo(['pk' => '1234']);
        $this->assertEquals(['pk' => '1234'], $this->sut->getTransactionInfo());
    }

}
