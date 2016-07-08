<?php

namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use AtomicDeploy\Client\Config;

class Application extends SymfonyApplication {

    protected $config = null;

    public function __construct(Config $config, $name = 'UNKNOWN', $version = 'UNKNOWN') {
        parent::__construct($name, $version);
        $this->config = $config;
    }

    /**
     * Returns the configuration object
     */
    public function getConfig() {
         return $this->config;
    }
}
