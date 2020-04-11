<?php


namespace AtomicDeploy\Client;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use AtomicDeploy\Client\Config;
use Illuminate\Support\Str;

class TaskManager {

    public function __construct(Config $config, OutputInterface $output) {
        $this->config = $config;
        $this->output = $output;
    }

    public function listDeploymentsOnServer() {
        $skip = [$this->config['path.shared'], $this->config['path.current'], $this->config['path.next']];
        $deployments = explode("\n", $this->runCommandOnServer(['ls', '.'], ['captureOutput' => true]));
        $return = [];
        foreach($deployments as $deployment) {
            if(in_array($deployment, $skip)) {
                continue;
            }
            $return[] = $deployment;
        }
        return $return;
    }

    public function runShellCommandOnClient($command, $currentWorkingDirectory = null) {
        $process = new Process($command, $currentWorkingDirectory);
        $process->setTimeout(0);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type && $this->output instanceof ConsoleOutputInterface) {
                $this->output->getErrorOutput()->write($buffer);
            } else {
                $this->output->write($buffer);
            }
        });
        if(!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function onServer($command) {
        return $this->runCommandOnServer($command, ['log' => true]);
    }

    public function runCommandOnClient(Application $app, $command) {
        $this->output->writeln('');
        $this->output->writeln('<comment>[Client]</comment> ' . $command);
        $this->output->writeln('');

        $cmd = $app->find(head(explode(' ', $command)));
        $return = $cmd->run(new StringInput($command), $this->output);
        if($return !== 0) {
            throw new \Exception('Command failed: ' . $command);
        }
        return $cmd;
    }

    public function runCommandOnServer($command, $options = []) {
        if(!isset($options['captureOutput'])) {
            $options['captureOutput'] = false;
        }
        if(!isset($options['useBuffer'])) {
            $options['useBuffer'] = true;
        }
        if(!isset($options['log'])) {
            $options['log'] = false;
        }

        $commandJson = json_encode($command);
        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }

        $url = $this->config['server.url'] . '?command=' . urlencode($commandJson) . '&config=' . urlencode(json_encode($this->getServerConfig()));

        if($options['log']) {
            $this->output->writeln('');
            $this->output->writeln('<comment>[Server]</comment> ' . implode(' ', $command));
            $this->output->writeln('');
        }

        $request = curl_init($url);

        if($options['captureOutput']) {
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        } else {
            curl_setopt($request, CURLOPT_WRITEFUNCTION, function($cp, $data) {
                if(Str::contains($data, 'STOP_DEPLOYMENT')) {
                    throw new \Exception('Server Task Failed');
                }
                $this->output->write($data);
                return strlen($data);
            });
        }

        if(!$options['useBuffer']) {
            curl_setopt($request, CURLOPT_BUFFERSIZE, 0);
        }

        curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($request, CURLOPT_USERPWD, $this->config['server.username'] . ":" . $this->config['server.password']);
        $return = curl_exec($request);
        if($return === false) {
            throw new \Exception('cURL returned an error ' . curl_error($request));
        }
        curl_close($request);
        return trim($return);
    }

    private function getServerConfig() {
        return [
            'path' => $this->config['path'],
            'copy' => $this->config['copy'],
            'basePath' => $this->config['basePath'],
            'shared' => $this->config['shared']
        ];
    }

}
