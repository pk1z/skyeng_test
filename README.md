Задание: Проведите Code Review. Необходимо написать, с чем вы не согласны и почему.

Дополнительное задание: Напишите свой вариант.

Требования были: Добавить возможность получения данных от стороннего сервиса.

```
<?php

namespace src\Integration;

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

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

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

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }

    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}
```


* кеш не работает, т.к. данные в него не сохраняются
* кеш не работает, т.к. используется не ключ, а весь объект в json

* код не реализует паттерн декоратор
* нарушения PSR в неймспейсе

* улучшения - вынесен в конструктор захардкоженный параметр времени кеша


* Добавить type hint на аргументы и возвращаемые значения методов
* DecoratorManager нарушает SRP - он одновременно логирует и кеширует запросы - следует сделать два отдельных декоратора LoggingDataProvider, CachingDataProvider
* Декоратор реализован через наследование - из-за этого нет гибкости настройки декарируемого класса - не получится легко отключить кеширование, но оставить логирование или добавить дополнительные декораторы для других задач, изменять их порядок выполнения, комбинировать и т.д. Также такой декоратор сложнее протестировать. Нужно реализовать декораторы с использованием композиции
* Декоратор изменяет интерфейс исходного класса - getResponse вместо get. Теряется смысл использования декоратора - расширить поведение не изменяя интерфейса. Нужно выделить интерфейс DataProviderInterface, а DataProvider и декораторы должны его реализовывать
* Передача зависимости через сеттер setLogger. Эта зависимость обязательна для работы. Ее следует перенести в конструктор. Если разработчик забудет вызывать этот сеттер, то класс будет не верно работать.
* Обработка исключений в getResponse. В случае какого-либо исключения вызывающий код не сможет отличить пустой ответ от ошибки и не сможет корректно обработать эту ситуацию. Лучше дать возможность отловить и обработать ошибку на уровне выше
* Метод getCacheKey. По PSR-6 ключ для кеша не должен содержать {}, а в результате json_encode можно получить такой ключ. Лучше воспользоваться каким-либо алгоритмом хеширования для получения хеша. Кроме того, его лучше вынести из класса, а в декоратор передать зависимость новую CackeKeyGeneratorInterface. Тогда алгоритм получения ключа можно будет кастомизировать без изменения самого декоратора
* Срок годности кэшированного значения жестко задан в коды - из-за этого нельзя изменить этот параметр без изменения класса. Нужно добавить соответствующий параметр в конструктор

