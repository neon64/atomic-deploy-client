<?php


namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AtomicDeploy\Client\TaskManager;

class RunCommand extends Command {

    protected function configure() {
        $this->setName('run')
             ->setDescription('Runs an arbitrary command on the server')
            ->addArgument('task', InputArgument::REQUIRED, 'the command to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $run = new TaskManager($this->getApplication()->getConfig(), $output);
        $run->onServer($input->getArgument('task'));
    }

}
