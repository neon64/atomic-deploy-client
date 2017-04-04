<?php


namespace AtomicDeploy\Client\Vendor;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use AtomicDeploy\Client\Config;
use AtomicDeploy\Client\TaskManager;

class LFTPVendorUpdater implements VendorUpdater {

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param                                                   $directory
     * @param Transfer                                          $transfer
     */
    public function update(OutputInterface $output, $directory, VendorUpdateOperation $transfer) {
        $command = $this->getLFTPTransferCommand($output, $directory, $transfer);
        if($output->isVeryVerbose()) {
            $output->writeln('<info>Executing the whopper:</info>' . "\n" . $command);
        } else {
            $output->writeln('<info>Running lftp</info>');
        }

        $run = new TaskManager($this->config, $output);
        $run->runShellCommandOnClient($command);
    }

    protected function getLFTPTransferCommand(OutputInterface $output, $directory, VendorUpdateOperation $transfer) {
        $command = 'lftp -c ';
        $exec = [];
        $exec[] = 'open -u ' . $this->config['ftp.username'] . ',' . $this->config['ftp.password'] . ' -p ' . $this->config['ftp.port'] . ' ' . $this->config['ftp.host'];
        $exec[] = 'set ftp:passive-mode ' . ($this->config['ftp.passive'] ? 'true' : 'false');
        $exec[] = 'set net:timeout ' . $this->config['ftp.timeout'];
        $exec[] = 'set ssl:verify-certificate no';
        $exec[] = 'set ftp:ssl-allow false';

        $exclude = $this->getLFTPExcludeInfo($transfer);

        $i = 0;
        $count = count($transfer->changedPackages);
        foreach($transfer->changedPackages as $package) {
            $relative = $transfer->vendorPath . '/' . $package;
            $exec[] = 'echo ' . escapeshellarg($output->getFormatter()->format(" - Transferring $i / $count - <info>" . $package . "</info>"));
            $exec[] = 'mirror -R --ignore-time --delete --no-symlinks ' . $exclude . ' --verbose ' .
                escapeshellarg($relative) . ' ' .
                escapeshellarg($this->getRemoteBasePath($directory) . '/' . $relative);
            $i++;
        }

        $command .= '"' . implode('; ', $exec) . '"';
        return $command;
    }

    private function getLFTPExcludeInfo(VendorUpdateOperation $transfer) {
        $info = [];
        foreach($this->config['copy.excludePaths'] as $path) {
            if(starts_with($path, $transfer->vendorPath)) {
                $trimmedPath = substr($path, strlen($transfer->vendorPath));
                $info[] = '--exclude ' . escapeshellarg($trimmedPath);
            }
        }
        foreach($this->config['copy.excludeNames'] as $name) {
            $info[] = '--exclude-glob \'**/' . $name . '/**\'';
            $info[] = '--exclude-glob \'' . $name . '/**\'';
        }
        return implode(' ', $info);
    }

    protected function getRemoteBasePath($name) {
        return $this->config['basePath.ftp'] . '/' . $name;
    }
}
