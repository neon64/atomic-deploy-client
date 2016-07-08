<?php


namespace AtomicDeploy\Client\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use AtomicDeploy\Client\Config;

class PushCommand extends Command {

    protected function configure() {
        $this->setName('push')
             ->setDescription('Deploys the project.')
             ->addOption('force', null, InputOption::VALUE_NONE, 'force deploy even if no updates have been made');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $config = $this->getApplication()->getConfig();

        $current = $config['path.current'];
        $next = $config['path.next'];

        $run = new TaskManager($config, $output);

        $head = $run->getCurrentCommit();
        $output->writeln('<info>Current commit hash:</info> ' . $head);

        $deployments = explode("\n", $run->runCommandOnServerCapture('list'));
        if(in_array($head, $deployments)) {
            $output->writeln('<info>Commit already deployed</info>');
            if($input->getOption('force')) {
                $output->writeln('Deploying anyway');
            } else {
                return;
            }
        } else {
            $run->runCommandOnServer('rename ' . $next. ' ' . $head);
        }

        $run->runCommandOnServer('copy ' . $current . ' ' . $head, ['useBuffer' => false]);
        $run->runCommandOnClient($this->getApplication(), 'git:ftp push ' . $head);
        $composerTransferInstalled = $run->runCommandOnClient($this->getApplication(), 'composer:transfer-installed ' . $head);
        if($composerTransferInstalled->numTransferred > 0 || $input->getOption('force')) {
            $run->runCommandOnServer('composer:run-script ' . $head . ' post-update-cmd');
        }
        $run->runCommandOnServer('link:update-shared ' . $head . ' ' . $config['path.shared']);

        if($input->isInteractive()) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Every looks good. Make the switch?</question>', true);

            $output->writeln('');
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<error>Aborting</error>');
                return;
            }

            $output->writeln('Beaming it up, Scotty');
        }

        $run->runCommandOnServer('link:update ' . $current . ' ' . $head);

        $output->writeln('');
        $output->writeln("========================================\n" .
                         "            <info>Website Deployed</info>\n" .
                         "========================================");
        $output->writeln("\nCommit " . $head . " has been deployed successfully.\nYou will now be able to use the new system.");
        $output->writeln("This script will now run some extra tasks in preparation for the next deployment.\nYou may cancel them if you want, but it will just make things slower for next time.");

        $run->runCommandOnServer('copy ' . $current . ' ' . $next, ['useBuffer' => false]);
    }

}
