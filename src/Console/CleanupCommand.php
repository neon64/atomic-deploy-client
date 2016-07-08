<?php

namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use AtomicDeploy\Client\TaskManager;
use AtomicDeploy\Client\Git;

class CleanupCommand extends Command {

    protected function configure() {
        $this
            ->setName('cleanup')
            ->setDescription('Deletes all versions that isn\'t current');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $run = new TaskManager($this->getApplication()->getConfig(), $output);
        $git = new Git($this->getApplication()->getConfig(), $output);

        $exclude = $git->getCurrentCommitOnServer($run);
        $deployments = explode("\n", $run->runCommandOnServer('list', ['captureOutput' => true]));
        $toRemove = [];
        foreach($deployments as $item) {
            if($item !== $exclude) {
                $toRemove[] = $item;
            }
        }

        if(count($toRemove) > 0) {
            $output->writeln('<info>' . count($toRemove) . ' versions to remove</info>');
        } else {
            $output->writeln('<info>All clean</info>');
        }

        foreach($toRemove as $item) {
            if($input->isInteractive()) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('<question>Are you sure you want to remove version ' . $item . '?</question>', true);

                $output->writeln('');
                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('<error>Aborting</error>');
                    return;
                }
            }
            $run->runCommandOnServer('delete ' . $item, ['log' => true]);
        }
    }

}
