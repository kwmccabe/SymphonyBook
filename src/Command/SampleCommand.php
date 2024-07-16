<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Process\Process;

use Symfony\Contracts\Cache\CacheInterface;


#[AsCommand(
    name: 'app:sample',
    description: 'A sample command',
    hidden: false,
)]
class SampleCommand extends Command
{
    public function __construct(
         private CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(__METHOD__);
        //$output->writeln('input class: '.get_class($input));
        //$output->writeln('output class: '.get_class($output));

        // more features
        //if (!$output instanceof ConsoleOutputInterface) {
        //    throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        //}

        $hello = $input->getArgument('hello');
        $case = strtolower($input->getOption('case'));
        $output->writeln('case:'.$case);

        $ln = 'Hello '.$hello;
        if (str_contains($case,'low')) { $ln = strtolower($ln); }
        if (str_contains($case,'up')) { $ln = strtoupper($ln); }
        $output->writeln($ln);

        // uncached
        //$process = new Process(['git', 'tag', '-l', '--points-at', 'HEAD']);
        //$process->mustRun();
        //$output->write($process->getOutput());

        // cached
//         $step = $this->cache->get('app.current_step', function ($item) {
//             $process = new Process(['git', 'tag', '-l', '--points-at', 'HEAD']);
//             $process->mustRun();
//             $item->expiresAfter(30);
//
//             return $process->getOutput();
//         });
//         $output->writeln($step);

        return Command::SUCCESS;
        // return Command::FAILURE;
        // return Command::INVALID
    }

    // InputArgument::REQUIRED
    // InputArgument::OPTIONAL
    // InputArgument::IS_ARRAY
    // InputArgument::IS_ARRAY | InputArgument::REQUIRED
    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('hello', InputArgument::OPTIONAL, 'Who are you?', 'World')
            ->addOption('case', 'c', InputArgument::OPTIONAL, 'Output case','default')
        ;


    }

}
