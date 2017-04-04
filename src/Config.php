<?php

namespace AtomicDeploy\Client;

/**
 * A really naive global object to store deployment config.
 */
class Config implements \ArrayAccess {

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function offsetGet($offset) {
        return array_get($this->config, $offset);
    }

    public function offsetSet($offset, $value) {
        array_set($this->config, $offset, $value);
    }

    public function offsetExists($offset) {
        throw new \Exception('Unimplemented');
    }

    public function offsetUnset($offset) {
        throw new \Exception('Unimplemented');
    }

}
