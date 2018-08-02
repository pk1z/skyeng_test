<?php

namespace Skyeng\Integration;

use Skyeng\DataInterface\DataProviderInterface;

class DataProvider implements DataProviderInterface
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
     * @param array $input
     *
     * @return mixed
     */
    public function get(array $input)
    {
        return 'test data';
    }
}
