# AtomicDeploy

This command utility connects with `atomic-deploy/server` in order to deploy websites with zero downtime using just FTP and a PHP script on the server.

## Configuration file

The `adp` command should be run from the directory of your website. That directory should contain a `deploy.php` file which stores configuration for the deployment. He is an example configuration file:

```
<?php

return [
    'ftp' => [
        'username' => getenv('FTP_USERNAME'),
        'password' => getenv('FTP_PASSWORD'),
        'host' => getenv('FTP_HOST'),
        'port' => 21,
        'timeout' => 90,
        'passive' => true
    ],

    'server' => [
        'url' => 'http://secure.example.com/deploy/index.php',
        'username' => getenv('DEPLOY_SERVER_USERNAME'),
        'password' => getenv('DEPLOY_SERVER_PASSWORD')
    ],

    'path' => [
        'shared' => 'shared',
        'current' => 'current',
        'next' => 'next'
    ],

    'basePath' => [
        /**
         * The path to the deployments folder, relative to this file.
         */
        'server' => '/path/on/server/to/website_deployments',

        /**
         * The path to the deployments folder, relative to the FTP root
         */
        'ftp' => 'website_deployments',
    ],

    'shared' => [
        '.env',
        'storage/framework',
        'storage/backups',
        'storage/logs',
        'storage/marketplace',
        'storage/proxies',
        'public/content',
        'public/deploy'
    ],

    'copy' => [
        'excludePaths' => [
            'storage',
            'public/vendor'
        ],

        'excludeNames' => [
            'tests',
            'test',
            'docs',
            'doc',
            '.git'
        ]
    ]
];
```
