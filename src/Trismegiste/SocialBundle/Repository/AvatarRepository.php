<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Repository;

use Trismegiste\Socialist\AuthorInterface;
use Trismegiste\SocialBundle\Security\Profile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * AvatarRepository is a repository for avatar
 */
class AvatarRepository
{

    protected $storage;

    public function __construct($path)
    {
        $this->storage = $path;
    }

    public function updateAvatar(AuthorInterface $author, Profile $profile, UploadedFile $fch = null)
    {
        if (is_null($fch)) {
            $abstracted = $profile->gender == 'xx' ? "00.jpg" : '01.jpg';
        } else {
            $abstracted = $this->getAvatarName($author->getNickname()) . '.' . $fch->getClientOriginalExtension();
            $fch->move($this->storage, $abstracted);
        }
        $author->setAvatar($abstracted);
    }

    protected function getAvatarName($nick)
    {
        // it is not a way to "crypt" or whatsoever, it's a way to avoid two things: 
        // * strange characters in filesystem when nicknames are utf-8 without collision
        //   (for example: "kurogané" & "kurogane")
        // * loose validation for parameters in routes
        return bin2hex($nick);
    }

    public function getAvatarAbsolutePath($filename)
    {
        return $this->storage . $filename;
    }

}