<?php declare(strict_types=1);

namespace POSBasicExample\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'swag-commands:example',
    description: 'Add a short description for your command',
)]
class ExampleCommand extends Command
{
    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Does something very special.');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('It works!');

        // Exit code 0 for success
        return 0;
    }
}
