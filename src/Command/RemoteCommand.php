<?php

namespace App\Command;

use App\Library\Remote;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoteCommand.
 *
 * @package App\Command
 */
class RemoteCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:remote';

    /**
     * @var Remote
     */
    private $remote;

    /**
     * RemoteCommand constructor.
     *
     * @param Remote $remote
     * @param string|null $name
     */
    public function __construct(Remote $remote, string $name = null)
    {
        $this->remote = $remote;

        parent::__construct($name);
    }

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Execute a remote key function')
            ->addArgument('key', InputArgument::OPTIONAL, 'The key to execute', 'home');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = strtoupper($input->getArgument('key'));

        $output->writeln("Sending key {$key} to {$this->remote->getHost()}");

        try {
            $this->remote->sendKey($key);

            $output->writeln("Successfully sent key {$key}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("An error occurred: {$e->getMessage()}");
        }

        return Command::FAILURE;
    }
}
