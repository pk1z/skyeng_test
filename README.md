[![Build Status](https://travis-ci.org/pk1z/skyeng_test.svg?branch=master)](https://travis-ci.org/pk1z/skyeng_test)
[![codecov](https://codecov.io/gh/pk1z/skyeng_test/branch/master/graph/badge.svg)](https://codecov.io/gh/pk1z/skyeng_test)

Задание: Проведите Code Review. Необходимо написать, с чем вы не согласны и почему.

Дополнительное задание: Напишите свой вариант.

Требования были: Добавить возможность получения данных от стороннего сервиса.

```
<?php

namespace src\Integration; //Нарушение PSR, путь 'src' лишний

class DataProvider
{
    private $host;
    private $user;
    private $password;

    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
    }
}
<?php

namespace src\Decorator; //Нарушение PSR, путь 'src' лишний

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

/**
* нарушение принципа SingleResponsibility, если компонент отвечает и за
* кэш, и за логгирование
*/
class DecoratorManager extends DataProvider
{
    public $cache;
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );
            /**
            * Здесь получанные данные нужно сохранить в cache.
            */
            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }
    /**
    * для создания ключа кэша необходимо использовать хэш ф-цию,
    * c фиксированной длинной ключа, например sha или md5
    */
    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}
```

#### Рефакторинг

* кеш не работает, т.к. данные в него не сохраняются
* кеш не работает, т.к. используется не ключ, а весь объект в json
* код не реализует паттерн декоратор
* нарушения PSR в неймспейсе ("src")
* улучшения - вынесен в конструктор захардкоженный параметр времени кеша
