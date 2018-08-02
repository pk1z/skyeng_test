<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Skyeng\DataInterface\DataProviderInterface;
use Skyeng\Decorator\CacheDecorator;
use Skyeng\Integration\DataProvider;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;


final class EmailTest extends TestCase
{
    CONST SLOW_BACKEND_EXECUTION_TIME = 1;

    public function testStraightFakeBackend(): void
    {
        $dataProviderStub = $this->getDataProviderMock();

        $time_start = microtime(true);
        $response =  $dataProviderStub->get([]);

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);

        $this->assertGreaterThanOrEqual(1, $execution_time);
    }

    public function testFileSystemCachedFakeBackend(): void
    {
        $dataProviderStub = $this->getDataProviderMock();

        $cache = new FilesystemAdapter('app.cache');
        $cache->clear();

        try {
            $cachedProvider = new CacheDecorator($dataProviderStub, $cache);
        } catch (Exception $e) {

            throw new \PHPUnit\Runner\Exception('cant get cache'. $e->getMessage());
        }

        $time_start = microtime(true);
        $response =  $dataProviderStub->get([1]);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);

        $this->assertGreaterThanOrEqual(1, $execution_time);

        $time_start = microtime(true);
        $response =  $cachedProvider->get([1]);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);

        $this->assertGreaterThanOrEqual(1, $execution_time);

        $time_start = microtime(true);
        $response =  $cachedProvider->get([1]);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);

        $this->assertLessThanOrEqual(1, $execution_time);

    }

    public function testCachedValueSameAsOriginal(): void
    {
        $dataProviderStub = $this->getDataProviderMock();

        $cache = new FilesystemAdapter('app.cache');
        $cache->clear();

        try {
            $cachedProvider = new CacheDecorator($dataProviderStub, $cache);
        } catch (Exception $e) {

            throw new \PHPUnit\Runner\Exception('cant get cache'. $e->getMessage());
        }

        $response =  $cachedProvider->get([1]);

        $this->assertEquals('first', $response);

    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | DataProviderInterface
     */
    private function getDataProviderMock(){

        $dataProviderStub = $this->getMockBuilder(DataProvider::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $dataProviderStub->method('get')
            ->willReturnCallback(
                function($input) {
                    sleep($this::SLOW_BACKEND_EXECUTION_TIME);
                    switch ($input) {
                        case [1]:
                            return 'first';
                        case [2]:
                            return 'second';
                        default:
                            return null;
                            break;
                    }
                }
            )
        ;

        return $dataProviderStub;
    }
}

