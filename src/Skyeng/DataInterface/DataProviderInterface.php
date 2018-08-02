<?php

namespace Skyeng\DataInterface;

interface DataProviderInterface
{
    /**
     * @param array $input
     *
     * @return mixed
     */
    public function get(array $input);
}
