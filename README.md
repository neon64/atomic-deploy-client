# AtomicDeploy

[![Latest Stable Version](https://poser.pugx.org/atomic-deploy/client/v/stable)](https://packagist.org/packages/atomic-deploy/client) [![License](https://poser.pugx.org/atomic-deploy/client/license)](https://packagist.org/packages/atomic-deploy/client)

This command utility connects with `atomic-deploy/server` in order to deploy websites atomically to hosting without SSH access.

**Why?** Its 2020, there's DigitalOcean, Docker, microservices, Azure, Heroku. Why do I need this? Well the unfortunate reality is many clients are still stuck on ancient shared web hosting. This gives a slightly nicer experience for the developer whilst still working without SSH access.

**How?** Though there are FTP components built into this client, namely `GitFtpCommand` and `ComposerTransferInstalledCommand`, the current recommended way is to pull each deployment from scratch from a Git repository. `atomic-deploy/server` will then facilitate running things like `composer install`, despite not having any SSH access to the server itself.

*AtomicDeploy* will allow you to have multiple deployments of your website on the server at the same time, and they can be switched between using an atomic `rename` file operation which just switches a symlink over. In the ideal world, this will allow for zero-downtime deployments.

## Security

Of course, the `atomic-deploy/server` installation needs to be placed behind some form of password barrier, as it is basically as powerful as cPanel when it comes to wreaking havoc on a shared webhosting installation.

## Usage

To see a list of current deployments, run

    $ adp ls

To push the latest version

    $ adp push

To switch between versions

    $ adp use [desired_git_commit]

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
