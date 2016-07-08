<?php


namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use AtomicDeploy\Client\TaskManager;

class GitFtpCommand extends Command {

    protected function configure() {
        $this
            ->setName('git:ftp')
            ->setDescription('Updates files tracked by Git using git-ftp')
            ->addArgument('mode', InputArgument::REQUIRED, 'either `push` or `init`')
            ->addArgument('name', InputArgument::REQUIRED, 'the deployment to update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument('name');
        $mode = $input->getArgument('mode');
        if($mode !== 'push' && $mode !== 'init') {
            throw new InvalidArgumentException('`mode` must be one of: `push`, `init`');
        }

        $command = [
            __DIR__ . '/../../lib/git-ftp/git-ftp',
            escapeshellarg($mode),
            '-u', escapeshellarg($config['ftp.username']),
            '-p', escapeshellarg($config['ftp.password']),
            escapeshellarg($config['ftp.host'] . ':' . $config['ftp.port'] . '/' . $config['basePath.ftp'] . '/' . $name)
        ];
        $command = implode(' ', $command);

        $output->writeln('<info>Executing</info> ' . $command);

        $run = new TaskManager($this->getApplication()->getConfig(), $output);
        $run->runShellCommandOnClient($command, $output);
    }

}
