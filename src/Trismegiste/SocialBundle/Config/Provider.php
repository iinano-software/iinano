<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Config;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Trismegiste\OAuthBundle\DependencyInjection\ProviderConfigInterface;
use Trismegiste\Yuurei\Persistence\RepositoryInterface;

/**
 * Provider is a provider for dynamic config parameters coming from MongoDb
 *
 * @todo This object is obviously not SRP
 */
class Provider implements CacheWarmerInterface, ProviderInterface, \ArrayAccess, ProviderConfigInterface
{

    const FILENAME = 'social_config.php';

    /** @var \Trismegiste\Yuurei\Persistence\RepositoryInterface */
    protected $repo;
    protected $cacheDir;
    protected $defaultParam;
    // to prevent multiple loading :
    private $loadedConfig = null;

    /**
     * Ctor
     *
     * @param RepositoryInterface $repo
     * @param string $cache_dir
     * @param array $default
     */
    public function __construct(RepositoryInterface $repo, $cache_dir, array $default)
    {
        $this->repo = $repo;
        $this->cacheDir = $cache_dir;
        $this->defaultParam = $default;
    }

    /**
     * @inheritdoc
     */
    public function write(array $param)
    {
        $obj = $this->getUniqueInstance();
        $obj->data = $param;
        $this->repo->persist($obj);
        $this->dump($this->cacheDir, $param);
    }

    /**
     * @inheritdoc
     */
    public function read($forceReload = false)
    {
        if ($forceReload) {
            $cfg = $this->getUniqueInstance();
            $this->loadedConfig = $cfg->data;
        }

        if (is_null($this->loadedConfig)) {
            $this->loadedConfig = require $this->cacheDir . DIRECTORY_SEPARATOR . self::FILENAME;
        }

        return $this->loadedConfig;
    }

    /**
     * @inheritdoc
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function warmUp($cacheDir)
    {
        $c = $this->getUniqueInstance();
        $this->dump($cacheDir, $c->data);
    }

    protected function dump($cacheDir, array $obj)
    {
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . self::FILENAME
                , '<?php return ' . var_export($obj, true) . ';'
        );
    }

    /**
     * Get the unique entity in database (or create it)
     *
     * @return ParameterBag
     */
    protected function getUniqueInstance()
    {
        $singleton = $this->repo->findOne(['-class' => 'config']);

        if (is_null($singleton)) {
            $singleton = new ParameterBag($this->defaultParam);
        }

        return $singleton;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->read());
    }

    public function offsetGet($offset)
    {
        // no exist check by design
        return $this->read()[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Changing dynamic config is only possible with write()");
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException("Changing dynamic config is only possible with write()");
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        $listing = [];

        if (!empty($this['oauth_provider'])) {
            foreach ($this['oauth_provider'] as $pro => $cfg) {
                if (!is_null($cfg)) {
                    $listing[$pro] = $cfg;
                }
            }
        }

        return $listing;
    }

}
