<?php

namespace AtomicDeploy\Client;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Git {

    protected $config;
    protected $output;

    public function __construct(Config $config, OutputInterface $output) {
        $this->config = $config;
        $this->output = $output;
    }

    public function getCurrentCommit() {
        $process = new Process('git rev-parse --short HEAD');
        $process->run();
        if(!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        return trim($process->getOutput());
    }

    public function getCommitInfo($hash) {
        $process = new Process('git show --format=' . escapeshellarg($this->output->getFormatter()->format('<comment>%h</comment> <info>%s</info> - by %an, commited on %ad')) . ' --abbrev-commit --quiet ' . escapeshellcmd($hash));
        $process->run();
        if(!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        return trim($process->getOutput());
    }

    public function getCurrentCommitOnServer(TaskManager $taskManager) {
        return $taskManager->runCommandOnServer(
            'link:read ' . $this->config['path.current'],
            ['captureOutput' => true]
        );
    }
}
