<?php


namespace KimaiPlugin\SharedProjectTimesheetsBundle\Command;


use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallCommand extends Command
{
    public static $defaultName = 'kimai:bundle:' . self::BUNDLE_IDENTIFIER . ':install';
    const BUNDLE_IDENTIFIER = 'shared-project-timesheets';
    const BUNDLE_NAME = 'SharedProjectTimesheetsBundle';

    /**
     * @var string
     */
    private $pluginDir;

    public function __construct(string $pluginDir)
    {
        parent::__construct(self::$defaultName);
        $this->pluginDir = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::getDefaultName())
            ->setDescription('Install bundle/plugin ' . self::BUNDLE_IDENTIFIER)
            ->setHelp('This command will install the ' . self::BUNDLE_NAME . ' database.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->importMigrations($io, $output);
        } catch (Exception $ex) {
            $io->error('Failed to install ' . self::BUNDLE_NAME . ' database: ' . $ex->getMessage());
            return 1;
        }

        $io->success('Congratulations! ' . self:: BUNDLE_NAME . ' was successful installed!');
        return 0;
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        $config = $this->pluginDir . '/' . self::BUNDLE_NAME . '/Migrations/' . self::BUNDLE_IDENTIFIER . '.yaml';

        // prevent windows from breaking
        $config = str_replace('/', DIRECTORY_SEPARATOR, $config);

        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true, '--configuration' => $config]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);

        $io->writeln('');
    }

}