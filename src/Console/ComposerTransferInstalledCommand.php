<?php

namespace AtomicDeploy\Client\Console;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AtomicDeploy\Client\Config;
use AtomicDeploy\Client\Json;
use AtomicDeploy\Client\Vendor\VendorUpdater;
use AtomicDeploy\Client\Vendor\VendorUpdateOperation;
use Touki\FTP\Connection\Connection;
use Touki\FTP\FTPFactory;
use Touki\FTP\Model\Directory;
use Touki\FTP\Model\File;

class ComposerTransferInstalledCommand extends Command {

    public $numTransferred;

    protected $vendorPath = 'vendor';
    protected $installedDotJsonPath = null;
    protected $vendorUpdater;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $deployName;

    public function __construct(VendorUpdater $vendorUpdater) {
        parent::__construct();
        $this->vendorUpdater = $vendorUpdater;
        $this->installedDotJsonPath = $this->vendorPath . '/composer/installed.json';
        $this->alwaysUpload = [
            [
                'name' => 'composer metadata',
                'path' => $this->vendorPath . '/composer',
                'type' => 'directory_no_recurse'
            ],
            [
                'name' => 'autoload.php',
                'path' => $this->vendorPath . '/autoload.php',
                'type' => 'file'
            ]
        ];
    }

    protected function configure() {
        $this->setName('composer:transfer-installed')
            ->setDescription('Transfers updated packages via FTP')
            ->addArgument('name', InputArgument::REQUIRED, 'the deployment to update')
            ->addOption('force', null, InputOption::VALUE_NONE, 'run every step even if no changes were detected')
            ->addOption('transfer-all-packages', null, InputOption::VALUE_NONE, 'transfer all packages, disregarding the `installed.json` file on the server');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $this->deployName = $input->getArgument('name');

        $output->writeln('Loading local `installed.json`');
        $installedOnLocal = $this->getLocalInstalledDotJson();

        if($input->getOption('transfer-all-packages')) {
            $changed = [];
            foreach($installedOnLocal as $package) {
                $changed[] = $package['name'];
            }
            $output->writeln('<info>Transferring all packages</info>');
        } else {
            $output->writeln('Loading remote `installed.json`');
            try {
                $installedOnRemote = $this->getRemoteInstalledDotJson();
            } catch(Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $output->writeln('<error>Failed to load remote `installed.json`, using an empty list.</error>');
                $installedOnRemote = [];
            }
            $output->writeln('<info>Computing changed packages</info>');
            $changed = $this->getChangedPackages($installedOnLocal, $installedOnRemote);
        }


        $output->writeln(count($changed) . ' updated packages to transfer');

        if(count($changed) > 0 || $input->getOption('force')) {
            $op = new VendorUpdateOperation();
            $op->vendorPath = $this->vendorPath;
            $op->changedPackages = $changed;
            $this->vendorUpdater->update($this->output, $this->deployName, $op);

            $this->putExtraFiles();
        } else {
            $output->writeln('<info>Nothing to upload</info>');
        }

        $this->numTransferred = count($changed);

        return 0;
    }

    /**
     * Retrieves the contents of the local `installed.json`
     *
     * @throws \Exception
     * @return array
     */
    protected function getLocalInstalledDotJson() {
        return Json::decodeJson(file_get_contents($this->installedDotJsonPath));
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Touki\FTP\Exception\DirectoryException
     */
    protected function getRemoteInstalledDotJson() {
        $ftp = $this->getFTPConnection();

        $installedJson = $this->getRemotePath($this->installedDotJsonPath) ;
        $remote = $ftp->findFileByName($installedJson);
        if($remote === null) {
            throw new FileNotFoundException('`installed.json` was not found on the server at ' . $installedJson);
        }
        $local = '_server_installed.json';
        $ftp->download($local, $remote);
        $contents = Json::decodeJson(file_get_contents($local));
        unlink($local);
        return $contents;
    }

    /**
     * Puts composer stuff like `installed.json` and `autoload.php` up to the server
     */
    protected function putExtraFiles() {
        $ftp = $this->getFTPConnection();
        $this->output->writeln('<info>Uploading extra files</info>');

        foreach($this->alwaysUpload as $item) {
            $remote = $this->getRemotePath($item['path']);
            if($item['type'] == 'directory_no_recurse') {
                $this->output->writeln('Deleting contents of ' . $remote);
                $contents = $ftp->findFiles(new Directory($remote));
                foreach($contents as $file) {
                    $this->output->writeln('Removing old file `' . $file->getRealpath() . '`');
                    $ftp->delete($file);
                }
                $ftp->create(new Directory($remote));
                foreach(new \FilesystemIterator($item['path']) as $item) {
                    if(!$item->isFile()) { continue; }
                    $this->output->writeln('Transferring file `' . $item->getPathName() . '``');
                    $ftp->upload(new File($this->getRemotePath($item->getPathName())), $item->getPathName());
                }
            } else if($item['type'] == 'file') {
                $this->output->writeln('Transferring file `' . $item['path'] . '``');
                $ftp->upload(new File($remote), $item['path']);
            } else {
                throw new \Exception('Invalid type ' . $item['type']);
            }
        }
    }

    protected function getChangedPackages(array $first, array $second) {
        $changes = [];

        // finds added/changed packages
        foreach($first as $package) {
            $onServer = false;
            foreach($second as $otherPackage) {
                if($package['name'] == $otherPackage['name']) {
                    $onServer = true;
                    if($package !== $otherPackage) {
                        $changes[] = $package['name'];
                    }
                }
            }
            if(!$onServer) {
                $changes[] = $package['name'];
            }
        }

        // finds removed packages
        foreach($second as $package) {
            $onClient = false;
            foreach($first as $package) {
                if($package['name'] == $otherPackage['name']) {
                    $onClient = true;
                }
            }
            if(!$onClient) {
                $changes[] = $package['name'];
            }
        }

        return $changes;
    }

    protected function getRemotePath($name) {
        return $this->getApplication()->getConfig()['basePath.ftp'] . '/' . $this->deployName . '/' . $name;
    }

    protected function getFTPConnection() {
        $config = $this->getApplication()->getConfig();
        $connection = new Connection(str_replace('ftp://', '', $config['ftp.host']), $config['ftp.username'], $config['ftp.password'], $config['ftp.port'], $config['ftp.timeout'], $config['ftp.passive']);
        $factory = new FTPFactory();
        return $factory->build($connection);
    }

}
