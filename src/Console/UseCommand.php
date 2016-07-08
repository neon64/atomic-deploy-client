<?php


namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use AtomicDeploy\Client\TaskManager;

class UseCommand extends Command {

    protected function configure() {
        $this->setName('use')
             ->setDescription('Sets the current deployment to use')
            ->addArgument('version', InputArgument::OPTIONAL, 'the version/deployment to use');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $run = new TaskManager($this->getApplication()->getConfig(), $output);
        $version = $input->getArgument('version');
        if($version == null) {
            $helper = $this->getHelper('question');
            $versions = $run->listDeploymentsOnServer();
            $question = new ChoiceQuestion(
                'Please select the version to use:',
                $versions,
                0
            );
            $question->setErrorMessage('%s is not a valid version.');

            $version = $helper->ask($input, $output, $question);
        }
        $run->runCommandOnServer('link:update ' . $config['path.current'] . ' ' . $version);
    }

}
