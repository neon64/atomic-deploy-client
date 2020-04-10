<?php

namespace AtomicDeploy\Client;

use Illuminate\Support\Arr;

/**
 * A really naive global object to store deployment config.
 */
class Config implements \ArrayAccess {

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function offsetGet($offset) {
        return Arr::get($this->config, $offset);
    }

    public function offsetSet($offset, $value) {
        Arr::set($this->config, $offset, $value);
    }

    public function offsetExists($offset) {
        throw new \Exception('Unimplemented');
    }

    public function offsetUnset($offset) {
        throw new \Exception('Unimplemented');
    }

}
