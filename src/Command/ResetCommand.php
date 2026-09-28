<?php

namespace Wexample\SymfonyDataSyncDemo\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wexample\SymfonyDataSyncDemo\Service\DemoFileStore;
use Wexample\SymfonyDataSyncDemo\WexampleSymfonyDataSyncDemoBundle;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;

/**
 * Puts the demo contacts and remotes back as the fixtures describe them.
 */
class ResetCommand extends AbstractBundleCommand
{
    public function __construct(
        BundleService $bundleService,
        private readonly DemoFileStore $files,
    ) {
        parent::__construct($bundleService);
    }

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyDataSyncDemoBundle::class;
    }

    protected function configure(): void
    {
        $this->setDescription('Resets the data-sync demo contacts and remotes.');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->files->reset();
        $output->writeln('Demo data reset.');

        return self::SUCCESS;
    }
}
