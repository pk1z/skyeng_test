<?php

namespace Skyeng\Decorator;

use Exception;
use Psr\Log\LoggerInterface;
use Skyeng\DataInterface\DataProviderInterface;

class LoggerDecorator implements DataProviderInterface
{
    protected $dataProvider;
    protected $logger;

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     *
     * @return LoggerDecorator
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

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
     * @return LoggerDecorator
     */
    public function setDataProvider($dataProvider): LoggerDecorator
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     * @param DataProviderInterface $dataProvider
     * @param LoggerInterface       $logger
     *
     * @throws Exception
     */
    public function __construct(DataProviderInterface $dataProvider, LoggerInterface $logger)
    {
        try {
            $this
                ->setDataProvider($dataProvider)
                ->setLogger($logger)
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
            return $this->getDataProvider()->get($input);
        } catch (Exception $e) {
            $this->logger->critical('Error');
            throw $e;
        }
    }
}
