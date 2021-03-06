<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Tests\Unit\Repository;

use Trismegiste\SocialBundle\Repository\NetizenRepository;
use Trismegiste\SocialBundle\Security\Netizen;
use Trismegiste\SocialBundle\Security\Profile;
use Trismegiste\Socialist\Author;
use Trismegiste\Yuurei\Persistence\CollectionIterator;

/**
 * NetizenRepositoryTest tests NetizenRepository
 */
class NetizenRepositoryTest extends \PHPUnit_Framework_TestCase
{

    use \Trismegiste\SocialBundle\Tests\Helper\AssertSolid,
        \Trismegiste\SocialBundle\Tests\Helper\SecurityContextMock;

    /** @var NetizenRepository */
    protected $sut;
    protected $repository;
    protected $storage;

    protected function setUp()
    {
        $this->repository = $this->getMock('Trismegiste\Yuurei\Persistence\RepositoryInterface');
        $this->storage = $this->getMock('Trismegiste\SocialBundle\Repository\AvatarRepository', [], [], '', false);

        $this->sut = new NetizenRepository($this->repository, 'netizen', $this->storage);
    }

    public function testFindByNickname()
    {
        $this->repository->expects($this->once())
                ->method('findOne')
                ->with($this->equalTo(['-class' => 'netizen', 'author.nickname' => 'kirk']));
        $this->sut->findByNickname('kirk');
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidTypeFindByPk()
    {
        $this->repository->expects($this->once())
                ->method('findByPk')
                ->with($this->equalTo(123));
        $this->sut->findByPk(123);
    }

    public function testFindByPk()
    {
        $userMock = $this->getMockBuilder('Trismegiste\SocialBundle\Security\Netizen')
                ->disableOriginalConstructor()
                ->getMock();
        $this->repository->expects($this->once())
                ->method('findByPk')
                ->with($this->equalTo(123))
                ->will($this->returnValue($userMock));
        $this->sut->findByPk(123);
    }

    public function testPersist()
    {
        $this->storage->expects($this->once())
                ->method('updateAvatar');
        $this->repository->expects($this->exactly(2))
                ->method('persist');

        $user = new Netizen(new Author('kirk'));
        $user->setProfile(new Profile());
        $this->sut->persist($user);

        return $user;
    }

    /**
     * @depends testPersist
     */
    public function testUpdateAvatar(Netizen $user)
    {
        $this->storage->expects($this->once())
                ->method('updateAvatar');

        $img = \imagecreatetruecolor(10, 10);
        $this->sut->updateAvatar($user, $img);
    }

    public function testIsExistingNicknameFalse()
    {
        $this->assertFalse($this->sut->isExistingNickname('spock'));
    }

    public function testIsExistingNicknameTrue()
    {
        $this->repository->expects($this->once())
                ->method('findOne')
                ->will($this->returnValue("user"));

        $this->assertTrue($this->sut->isExistingNickname('spock'));
    }

    /**
     * @depends testPersist
     * @expectedException \RuntimeException
     */
    public function testSomethingWentWrongForUpdatingAvatar(Netizen $user)
    {
        $this->storage->expects($this->once())
                ->method('updateAvatar')
                ->will($this->throwException(new \Exception));

        $img = \imagecreatetruecolor(10, 10);
        $this->sut->updateAvatar($user, $img);
    }

    public function testFindBatchNickname()
    {
        $this->repository->expects($this->once())
                ->method('find')
                ->with($this->equalTo(['-class' => 'netizen', 'author.nickname' => ['$in' => ['spock']]]));

        $this->sut->findBatchNickname(new \ArrayIterator(['spock' => true]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadParamCtor()
    {
        new NetizenRepository($this->repository, 123, $this->storage);
    }

    protected function createIteratorMock()
    {
        return $this->getMockBuilder('Trismegiste\Yuurei\Persistence\CollectionIterator')
                        ->disableOriginalConstructor()
                        ->getMock();
    }

    public function testSearchUserByName()
    {
        $mockIterator = $this->createIteratorMock();
        $this->repository->expects($this->once())
                ->method('find')
                ->with($this->equalTo(['-class' => 'netizen', 'author.nickname' => new \MongoRegex('/^user/')]))
                ->willReturn($mockIterator);

        $this->sut->search(['nickname' => 'user']);
    }

    public function testSearchUserByRoleGroup()
    {
        $mockIterator = $this->createIteratorMock();
        $this->repository->expects($this->once())
                ->method('find')
                ->with($this->equalTo(['-class' => 'netizen', 'roleGroup' => 'user']))
                ->willReturn($mockIterator);

        $this->sut->search(['group' => 'user']);
    }

    public function testSearchOrdering()
    {
        $mockIterator = $this->createIteratorMock();
        $this->repository->expects($this->once())
                ->method('find')
                ->with($this->equalTo(['-class' => 'netizen']))
                ->willReturn($mockIterator);
        $mockIterator->expects($this->once())
                ->method('sort')
                ->with(['_id' => -1]);

        $this->sut->search(['sort' => '_id -1']);
    }

    /**
     * This because I don't want to forget new method in the interface
     */
    public function testInterfaceInSync()
    {
        $this->assertMethodCountEquals('Trismegiste\SocialBundle\Repository\NetizenRepository', [
            'Trismegiste\SocialBundle\Repository\NetizenRepositoryInterface'
                ], 1);
    }

    public function testCountAllUsers()
    {
        $this->repository->expects($this->once())
                ->method('getCursor')
                ->with($this->equalTo(['-class' => 'netizen']))
                ->willReturn(new \ArrayObject([1, 2, 3]));

        $this->assertEquals(3, $this->sut->countAllUser());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage You have no right to promote someone
     */
    public function testPromoteNetizenWithoutRights()
    {
        $secu = $this->createSecurityContextMock(new Author('spock'));

        $user = new Netizen(new Author('kirk'));
        $this->sut->promote($user, $secu);
    }

    public function testPromoteNetizenWithRights()
    {
        $secu = $this->createSecurityContextMock(new Author('spock'));
        $secu->expects($this->once())
                ->method('isGranted')
                ->with('ROLE_PROMOTE')
                ->willReturn(true);

        $this->repository->expects($this->once())
                ->method('persist');

        $user = new Netizen(new Author('kirk'));
        $this->sut->promote($user, $secu);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage You can't promote yourself
     */
    public function testNoPromoteNetizenOnHimself()
    {
        $user = new Netizen(new Author('spock'));
        $secu = $this->createSecurityContextMockFromUser(clone $user);
        $user->setGroup('ROLE_MANAGER');

        $this->sut->promote($user, $secu);
    }

    public function testFindLastRegistered()
    {
        $cursor = $this->getMockBuilder('MongoCursor')
                ->disableOriginalConstructor()
                ->getMock();
        $cursor->expects($this->once())
                ->method('sort');
        $cursor->expects($this->once())
                ->method('limit');

        $this->repository->expects($this->once())
                ->method('find')
                ->with($this->equalTo(['-class' => 'netizen']))
                ->willReturn(new CollectionIterator($cursor, $this->repository));

        $this->sut->findLastRegistered();
    }

    public function testCountOnPeriod()
    {
        $cursor = $this->getMockBuilder('MongoCursor')
                ->disableOriginalConstructor()
                ->getMock();
        $cursor->expects($this->once())
                ->method('count');

        $this->repository->expects($this->once())
                ->method('getCursor')
                ->with($this->arrayHasKey('-class'))
                ->willReturn($cursor);

        $this->sut->countOnLastPeriod(123);
    }

}
