<?php

namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AtomicDeploy\Client\Config;
use AtomicDeploy\Client\Git;
use AtomicDeploy\Client\TaskManager;

class ListCommand extends Command {

    protected function configure() {
        $this->setName('ls')
             ->setDescription('Lists the deployments of the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $git = new Git($this->getApplication()->getConfig(), $output);
        $run = new TaskManager($this->getApplication()->getConfig(), $output);

        $local = $git->getCurrentCommit();
        $output->writeln('<info>Current commit hash:</info> ' . $local);
        $server = $git->getCurrentCommitOnServer($run);
        if($local == $server) {
            $output->writeln('Server up to date');
        } else {
            $output->writeln('Server differs: ' . $server);
        }

        $deployments = $run->listDeploymentsOnServer();
        $output->writeln("\n" . '<info>All versions</info>:');
        foreach($deployments as $deployment) {
            $output->writeln(' - ' . $git->getCommitInfo($deployment));
            //$output->writeln('');
            //$output->writeln(' - ' . $deployment);
        }

        return 0;
    }

}
