<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Repository;

use Trismegiste\Socialist\AuthorInterface;
use Trismegiste\SocialBundle\Repository\PictureRepository;

/**
 * AvatarRepository is a repository for avatar
 *
 * This is an Adapter onto PictureRepository
 */
class AvatarRepository
{

    protected $storage;

    public function __construct(PictureRepository $repo)
    {
        $this->storage = $repo;
    }

    /**
     * Update the author's avatar with a given GD image resource
     * Persists the image file and update the property of Author
     * !! No persistence on Author, only edge effect !!
     *
     * @param AuthorInterface $author the author to update
     * @param resource $imageResource a GD image resource
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function updateAvatar(AuthorInterface $author, $imageResource)
    {
        if (!is_resource($imageResource)) {
            throw new \InvalidArgumentException('The image is not a valid resource');
        }

        try {
            // always the same name
            $avatarName = sha1('avatar' . $author->getNickname()) . '.jpeg';

            $this->storage->upsertResource($avatarName, $imageResource);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to save avatar');
        }

        $author->setAvatar($avatarName);
    }

    public function getIcon($code)
    {
        $fch = 'avatar-' . ($code === 'xx' ? 'xx' : 'xy') . '.jpg';

        return \imagecreatefromjpeg(__DIR__ . '/../Resources/icon/' . $fch);
    }

}
