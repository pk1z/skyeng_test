<?php

namespace Skyeng\Decorator;

use DateInterval;
use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Skyeng\DataInterface\DataProviderInterface;

class CacheDecorator implements DataProviderInterface
{
    private $cache;
    private $ttlInterval;
    private $cacheNamespace;

    /**
     * @return mixed
     */
    public function getCacheNamespace()
    {
        return $this->cacheNamespace;
    }

    /**
     * @param mixed $cacheNamespace
     *
     * @return CacheDecorator
     */
    public function setCacheNamespace($cacheNamespace)
    {
        $this->cacheNamespace = $cacheNamespace;

        return $this;
    }

    /**
     * @return DateInterval
     */
    public function getTtlInterval()
    {
        return $this->ttlInterval;
    }

    /**
     * @param DateInterval $ttlInterval
     *
     * @return CacheDecorator
     */
    public function setTtlInterval($ttlInterval)
    {
        $this->ttlInterval = $ttlInterval;

        return $this;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return CacheDecorator
     */
    public function setCache(CacheItemPoolInterface $cache): CacheDecorator
    {
        $this->cache = $cache;

        return $this;
    }

    protected $dataProvider;

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param mixed $dataProvider
     *
     * @return CacheDecorator
     */
    public function setDataProvider($dataProvider): CacheDecorator
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     * @param DataProviderInterface  $dataProvider
     * @param CacheItemPoolInterface $cache
     * @param string                 $ttlInterval
     * @param string                 $cacheNamespace
     *
     * @throws Exception
     */
    public function __construct(
        DataProviderInterface $dataProvider,
        CacheItemPoolInterface $cache,
        $ttlInterval = 'P1D',
        $cacheNamespace = 'default_namespace'
    ) {
        try {
            $this
                ->setDataProvider($dataProvider)
                ->setCache($cache)
                ->setTtlInterval(new DateInterval($ttlInterval))
                ->setCacheNamespace($cacheNamespace)
            ;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->getCache()->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = $this->getDataProvider()->get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->add($this->getTtlInterval())
                );

            $this
                ->getCache()
                ->save($cacheItem)
            ;

            //значение не кладётся в кеш
            return $result;
        } catch (InvalidArgumentException | Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $input
     *
     * @return string
     */
    private function getCacheKey(array $input)
    {
        return 'cache'.md5(ksort($input));
    }
}
