<?php

namespace App\Command;

use App\Library\RemoteClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RemoteClient
     */
    private $remoteClient;

    /**
     * RemoteCommand constructor.
     *
     * @param TranslatorInterface $translator
     * @param RemoteClient $remoteClient
     * @param string|null $name
     */
    public function __construct(
        TranslatorInterface $translator,
        RemoteClient $remoteClient,
        string $name = null
    ) {
        $this->translator = $translator;
        $this->remoteClient = $remoteClient;

        parent::__construct($name);
    }

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setDescription(
                $this->translator->trans('remote.command.description')
            )
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                $this->translator->trans('remote.command.argument.key'),
                'home'
            );
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
        // Retrieve the key.
        $key = strtoupper($input->getArgument('key'));

        $output->writeln(
            $this->translator->trans('remote.command.info', [
                'key' => $key,
                'host' => $this->remoteClient->getHost()
            ])
        );

        try {
            // Send the key.
            $this->remoteClient->sendKey($key);

            $output->writeln(
                $this->translator->trans('remote.command.success', [
                    'key' => $key
                ])
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(
                $this->translator->trans('remote.command.error', [
                    'message' => $e->getMessage()
                ])
            );
        }

        return Command::FAILURE;
    }
}
