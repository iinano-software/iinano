<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Repository;

use Trismegiste\Yuurei\Persistence\RepositoryInterface;
use Trismegiste\DokudokiBundle\Transform\Mediator\Colleague\MapAlias;
use Trismegiste\SocialBundle\Security\Netizen;
use Trismegiste\Socialist\Author;
use Trismegiste\SocialBundle\Security\Credential\Internal;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Trismegiste\SocialBundle\Security\Profile;

/**
 * NetizenRepository is a repository for Netizen (and also Author)
 *
 * @todo Is this a decorator ( ie implementing RepositoryInterface ) ?
 */
class NetizenRepository implements NetizenRepositoryInterface
{

    protected $repository;
    protected $classAlias;
    protected $encoderFactory;
    protected $storage;

    /**
     * Ctor
     *
     * @param RepositoryInterface $repo the repository for MongoCollection
     * @param EncoderFactoryInterface $encoderFactory the Security component factory which manages encoders for password
     * @param type $alias the class key alias for the Netizen objects stored with Dokudoki
     * @param \Trismegiste\SocialBundle\Repository\AvatarRepository $storage a repository for storing avatar pictures
     */
    public function __construct(RepositoryInterface $repo, EncoderFactoryInterface $encoderFactory, $alias, AvatarRepository $storage)
    {
        $this->repository = $repo;
        $this->classAlias = $alias;
        $this->encoderFactory = $encoderFactory;
        $this->storage = $storage;
    }

    /**
     * @inheritdoc
     */
    public function findByNickname($nick)
    {
        $obj = $this->repository->findOne([
            MapAlias::CLASS_KEY => $this->classAlias,
            'author.nickname' => $nick
        ]);

        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function create($nick, $password)
    {
        $author = new Author($nick);
        $user = new Netizen($author);

        $salt = \rand(100, 999);
        $encoded = $this->encoderFactory
                ->getEncoder($user) // @todo Demeter's law violation : inject encoder as a service with a factory ?
                ->encodePassword($password, $salt);
        $user->setCredential(new Internal($encoded, $salt));
        $user->setProfile(new Profile());

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function persist(Netizen $obj)
    {
        // pre-persist :
        if (is_null($obj->getAuthor()->getAvatar())) {
            // @todo parameter for config
            $avatarName = $obj->getProfile()->gender == 'xx' ? "00.jpg" : '01.jpg';
            $obj->getAuthor()->setAvatar($avatarName);
        }

        $this->repository->persist($obj);
    }

    /**
     * @inheritdoc
     */
    public function findByPk($id)
    {
        return $this->repository->findByPk($id);
    }

    /**
     * @inheritdoc
     */
    public function updateAvatar(Netizen $user, $imageResource)
    {
        try {
            $this->storage->updateAvatar($user->getAuthor(), $imageResource);
            $this->persist($user);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function isExistingNickname($nick)
    {
        return !is_null($this->findByNickname($nick));
    }

}
