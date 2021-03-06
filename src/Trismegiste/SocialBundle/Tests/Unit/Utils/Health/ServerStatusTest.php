<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Unit\Utils\Health;

use Trismegiste\SocialBundle\Utils\Health\ServerStatus;

/**
 * ServerStatusTest tests ServerStatus
 */
class ServerStatusTest extends \PHPUnit_Framework_TestCase
{

    /** @var ServerStatus */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new ServerStatus('eth0');
    }

    public function testCpu()
    {
        $this->assertCount(3, $this->sut->getCpuLoad());
    }

    public function testDisk()
    {
        $this->assertLessThan(1, $this->sut->getFreeSpaceRatio());
    }

    public function testMemory()
    {
        $this->assertArrayHasKey('MemFree', $this->sut->getMemoryLoad());
    }

    public function testBandwidth()
    {
        $this->assertArrayHasKey('rx', $this->sut->getMonthlyBandwidth());
        $this->assertArrayHasKey('tx', $this->sut->getMonthlyBandwidth());
    }

    public function testUnknowBandwidth()
    {
        $stat = new ServerStatus('waloo');
        $this->assertEquals(['tx' => 0, 'rx' => 0], $stat->getMonthlyBandwidth());
    }

}
