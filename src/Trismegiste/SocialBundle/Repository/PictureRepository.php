<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Repository;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Trismegiste\Socialist\Picture;
use Gregwar\Image\Image;

/**
 * PictureRepository is a storage repository for managing pictures and thumbnails
 */
class PictureRepository
{

    const MIMETYPE_REGEX = '#^image/(jpg|jpeg|gif|png)$#';
    const SLOW_FS_CHUNK = 4; // the directory chunk for slow filesystem
    const MAX_RES = 'full';

    /**
     * Absolute path to directory for max resolution pictures
     *
     * @var string
     */
    protected $storageDir;

    /**
     * Absolute path to directory for cached & resized pictures
     *
     * @var string
     */
    protected $cacheDir;
    protected $fallback;

    /**
     * Size configuration for pictures
     */
    protected $sizeConfig;

    public function __construct($storageDir, $cacheDir, array $sizeCfg)
    {
        $this->storageDir = realpath($storageDir);
        if (!$this->storageDir) {
            throw new \InvalidArgumentException("$storageDir is not a valid directory for storage");
        }
        $this->storageDir .= DIRECTORY_SEPARATOR;

        $this->cacheDir = realpath($cacheDir);
        if (!$this->cacheDir) {
            throw new \InvalidArgumentException("$cacheDir is not a valid directory for cache");
        }
        $this->cacheDir .= DIRECTORY_SEPARATOR;

        if (!array_key_exists(self::MAX_RES, $sizeCfg)) {
            throw new \InvalidArgumentException("The size configuration for Picture is invalid : '" . self::MAX_RES . "' key is missing");
        }
        $this->sizeConfig = $sizeCfg;
        $this->fallback = $this->storageDir . '/notfound.png';
    }

    /**
     * Stores an uploaded file to the storage and update a Picture document for this picture
     * (no persistence only file creation)
     *
     * @param Picture $pub
     * @param UploadedFile $picFile
     *
     * @throws \InvalidArgumentException Bad mimetype
     */
    public function insertUpload(Picture $pub, UploadedFile $picFile)
    {
        if (!$picFile->isValid()) {
            throw new \RuntimeException('Upload was incomplete');
        }
        $serverMimeType = $picFile->getMimeType();

        $nick = $pub->getAuthor()->getNickname();
        $extension = [];
        if (!preg_match(self::MIMETYPE_REGEX, $serverMimeType, $extension)) {
            throw new \InvalidArgumentException($serverMimeType . ' is not a valid mime type');
        }

        $syntheticName = $this->hashForNick($nick) . '.' . $extension[1];
        $pub->setMimeType($serverMimeType);
        $pub->setStorageKey($syntheticName);

        $path = $this->getStoragePath($syntheticName);

        // because diskspace is costly, I don't want to keep original picture
        // that's why I resize & recompress at a full-hd res (mobile first and I don't intend to
        // clone Picasa)
        Image::open($picFile->getPathname())
                ->setCacheDir($this->cacheDir)
                ->cropResize($this->sizeConfig[self::MAX_RES], $this->sizeConfig[self::MAX_RES])
                ->save($path);

        if (!file_exists($path)) {
            throw new \RuntimeException("Cannot save $syntheticName");
        }
        $pub->setFileSize(filesize($path));
    }

    /**
     * Upsert an image resource with a given storage key
     *
     * @param string $targetName storage key
     * @param resource $imgRsrc image resource
     */
    public function upsertResource($targetName, $imgRsrc)
    {
        $path = $this->getStoragePath($targetName);

        Image::fromResource($imgRsrc)
                ->setCacheDir($this->cacheDir)
                ->cropResize($this->sizeConfig[self::MAX_RES], $this->sizeConfig[self::MAX_RES])
                ->save($path);
    }

    protected function getStoragePath($filename)
    {
        return $this->storageDir
                . implode('/', str_split(substr($filename, 0, self::SLOW_FS_CHUNK)))
                . '/'
                . $filename;
    }

    /**
     * Get the absolute path of a picture with a given storageKey for a given size
     *
     * @param string $filename the storage key
     * @param string $size 'full'|'medium'|'tiny'|'whatever'
     *
     * @return string absolute path in the filesystem
     */
    public function getImagePath($filename, $size = self::MAX_RES)
    {
        if (!array_key_exists($size, $this->sizeConfig)) {
            throw new \OutOfBoundsException("$size is not a valid size");
        }

        $sourceImg = $this->getStoragePath($filename);
        if (!file_exists($sourceImg)) {
            return $this->fallback;
        }

        if (($size !== self::MAX_RES)) {
            $sourceImg = Image::open($sourceImg)
                    ->setCacheDir($this->cacheDir)
                    ->setFallback($this->fallback)
                    ->resize($this->sizeConfig[$size])
                    ->guess();
        }

        return $sourceImg;
    }

    /**
     * Get a hash for a storage key, because using client's name is Evil
     *
     * @param string $nick
     *
     * @return string
     */
    protected function hashForNick($nick)
    {
        return sha1($nick . microtime(false) . rand());
    }

    /**
     * Remove a picture in the storage (not in the database)
     *
     * @param string $storageKey
     */
    public function remove($storageKey)
    {
        $path = $this->getImagePath((string) $storageKey);
        @unlink($path);
    }

    public function clearCache($dayOld)
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $it = $finder->in($this->cacheDir)
                ->files()
                ->date("< now - $dayOld days")
                ->getIterator();

        $cpt = 0;
        foreach ($it as $file) {
            /* @var $file \Symfony\Component\Finder\SplFileInfo */
            $ret = @unlink($file->getPathname());
            if ($ret) {
                $cpt++;
            }
        }

        return $cpt;
    }

}
