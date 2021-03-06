<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Unit\Config;

use Trismegiste\SocialBundle\Config\Provider;
use Trismegiste\SocialBundle\Config\ParameterBag;

/**
 * ProviderTest tests the cachec config provider
 */
class ProviderTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Trismegiste\SocialBundle\Config\Provider */
    protected $sut;

    /** @var Trismegiste\Yuurei\Persistence\RepositoryInterface */
    protected $repository;
    protected $targetFile;

    protected function setUp()
    {
        $this->repository = $this->getMock('Trismegiste\Yuurei\Persistence\RepositoryInterface');
        $this->sut = new Provider($this->repository, sys_get_temp_dir(), ['default' => 123]);
        $this->targetFile = sys_get_temp_dir() . '/' . Provider::FILENAME;
        @unlink($this->targetFile);
    }

    public function testWriteNew()
    {
        $this->repository->expects($this->once())
                ->method('findOne')
                ->with(['-class' => 'config']);

        $this->repository->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('Trismegiste\SocialBundle\Config\ParameterBag'));

        $this->sut->write([]);
        $this->assertFileExists($this->targetFile);
    }

    public function testWriteExisting()
    {
        $this->repository->expects($this->once())
                ->method('findOne')
                ->with(['-class' => 'config'])
                ->willReturn(new ParameterBag(['database' => 789]));

        $this->repository->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('Trismegiste\SocialBundle\Config\ParameterBag'));

        $this->sut->write(['param' => 'ncc1701']);
        $this->assertFileExists($this->targetFile);
        $this->assertEquals(['param' => 'ncc1701'], $this->sut->read());
    }

    public function testRead()
    {
        $doc = ['config' => 456];
        $this->sut->write($doc);

        $this->assertEquals($doc, $this->sut->read());
    }

    public function testReadForceReload()
    {
        $doc = ['database' => 789];
        $this->repository->expects($this->once())
                ->method('findOne')
                ->with(['-class' => 'config'])
                ->willReturn(new ParameterBag($doc));

        $this->assertEquals($doc, $this->sut->read(true));
    }

    public function testCachedRead()
    {
        $doc = ['config' => 456];
        $this->sut->write($doc);

        $this->assertEquals($doc, $this->sut->read());
        @unlink($this->targetFile);
        $this->assertEquals($doc, $this->sut->read());
    }

    public function testNoOptionalWarmUp()
    {
        $this->assertFalse($this->sut->isOptional());
    }

    public function testWarmupWithDefaultValues()
    {
        $this->assertFileNotExists($this->targetFile);
        $this->sut->warmUp(sys_get_temp_dir());
        $this->assertFileExists($this->targetFile);
        $this->assertEquals(['default' => 123], $this->sut->read());
    }

    public function testWarmupWithDatabase()
    {
        $this->repository->expects($this->once())
                ->method('findOne')
                ->with(['-class' => 'config'])
                ->willReturn(new \Trismegiste\SocialBundle\Config\ParameterBag(['database' => 789]));

        $this->sut->warmUp(sys_get_temp_dir());
        $this->assertEquals(['database' => 789], $this->sut->read());
    }

    public function testArrayAccessGet()
    {
        $this->sut->write(['config' => 456]);
        $this->assertEquals(456, $this->sut['config']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testArrayAccessNoUnset()
    {
        unset($this->sut['nihil']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testArrayAccessNoSet()
    {
        $this->sut['nihil'] = 42;
    }

    public function testGetProviderKeys()
    {
        $this->sut->write(['oauth_provider' => ['facebook' => '']]);
        $this->assertArrayHasKey('facebook', $this->sut->all());
    }

}
