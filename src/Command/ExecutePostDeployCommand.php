<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Entity\PostDeployExecution;
use UbeeDev\LibBundle\Producer\SlackNotificationProducer;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Repository\PostDeployExecutionRepository;
use UbeeDev\LibBundle\Traits\StringTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'lib:post_deploy'
)]
class ExecutePostDeployCommand extends Command
{
    use StringTrait;

    private array $alreadyExecutedCommands = [];
    private OutputInterface $output;

    public function __construct(
        private readonly PostDeployExecutionRepository $postDeployExecutionRepository,
        private readonly EntityManagerInterface        $entityManager,
        private readonly ParameterBagInterface         $parameterBag,
        private readonly ContainerInterface            $container,
        private readonly SlackNotificationProducer     $slackNotificationProducer,
        private readonly KernelInterface               $kernel,
        private readonly ?string                       $postDeploySlackChannel = null,
        ?string                                        $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $postDeployDirectory = $this->getCommandPath();
        $this->output = $output;

        // Create and Check instance if postDeployDirectory exists
        if (!$this->ensureDirectoryExists($postDeployDirectory)) {
            $output->writeln('<info>No post deploy command to run</info>');
            return Command::SUCCESS;
        }

        // Create and Check instance if files in postDeployDirectory exists and has results
        $files = $this->fetchFilesInDirectory($postDeployDirectory);

        if (!$files) {
            $output->writeln('<info>No post deploy command to run</info>');
            return Command::SUCCESS;
        }

        $this->runCommands($files, $output);
        return Command::SUCCESS;
    }

    private function isAlreadyExecuted(string $className): bool
    {
        return array_filter($this->alreadyExecutedCommands, static function (PostDeployExecution $command) use ($className) {
                return $command->getName() === $className;
            }) !== [];
    }

    private function startRecord(): float
    {
        return microtime(true);
    }

    private function endRecord(): float
    {
        return microtime(true);
    }

    private function getDifferenceInMilliseconds(float $start, float $end): int
    {
        $differenceInSeconds = $end - $start;
        return $differenceInSeconds * 1000; // convert seconds to milliseconds
    }

    private function getCommandPath(): string
    {
        return $this->parameterBag->get('kernel.project_dir') . '/src/PostDeploy/';
    }

    private function ensureDirectoryExists(string $dir): bool
    {
        return (new Filesystem())->exists($dir);
    }

    private function fetchFilesInDirectory(string $dir): ?Finder
    {
        $finder = new Finder();
        // Find all files in the specified directory
        $files = $finder->files()->in($dir)->sortByName();
        return ($files->hasResults()) ? $files : null;
    }

    /**
     * @throws Exception
     */
    private function runCommands(Finder $files, OutputInterface $output): void
    {
        $this->alreadyExecutedCommands = $this->postDeployExecutionRepository->findAll();

        $nbCommandsToRun = 0;
        // Loop through the files
        foreach ($files as $file) {

            $fileNameWithoutExtension = $file->getFilenameWithoutExtension();
            if ($this->isAlreadyExecuted($fileNameWithoutExtension)) {
                continue;
            }

            $nbCommandsToRun++;

            $executionTime = $this->executeCommand($file);
            $this->savePostDeployExecutionInDatabase(
                $file,
                $executionTime,
            );
        }

        if (!$nbCommandsToRun) {
            $output->writeln('<info>No post deploy command to run</info>');
        }
    }

    private function sendSlackNotification(Exception $exception, string $message): void
    {
        if (!$this->postDeploySlackChannel) {
            return;
        }

        $this->slackNotificationProducer->publish(
            $this->postDeploySlackChannel,
            $message,
            new TextSnippet($exception->getMessage())
        );
    }

    /**
     * @throws Exception
     */
    private function executeCommand(SplFileInfo $file): int
    {
        $postDeployServiceName = $this->getClassNameWithNamespaceFromFile($file->getRealPath());
        try {
            $start = $this->startRecord();
            $this->container->get($postDeployServiceName)->execute($postDeployServiceName);
            $end = $this->endRecord();

        } catch (Exception $exception) {
            $this->sendSlackNotification(
                $exception,
                "[Warning] Error executing post deploy script \"*" . $postDeployServiceName . "*\" \n\nRerun command: \n```php " . $this->parameterBag->get('kernel.project_dir') . "/bin/console lib:post_deploy --env=" . $this->kernel->getEnvironment() . "```"
            );

            throw $exception;
        }

        return $this->getDifferenceInMilliseconds($start, $end);
    }

    private function savePostDeployExecutionInDatabase(
        SplFileInfo $file,
        int $executionTime
    )
    {
        $fileNameWithoutExtension = $file->getFilenameWithoutExtension();
        $postDeployServiceName = $this->getClassNameWithNamespaceFromFile($file->getRealPath());

        try {
            $postDeployExecution = new PostDeployExecution();
            $postDeployExecution->setName($fileNameWithoutExtension)
                ->setExecutedAt(new DateTime())
                ->setExecutionTime($executionTime);

            $this->entityManager->persist($postDeployExecution);
            $this->entityManager->flush();

            $this->output->writeln('<info>Executed ' . $fileNameWithoutExtension . ' in ' . $executionTime . ' milliseconds</info>');
        } catch (Exception $exception) {
            $this->sendSlackNotification(
                $exception,
                "[Warning] Error saving post deploy execution \"*" . $postDeployServiceName . "*\" \n\nTo prevent this script from running again on next deploy, manually add a row in the *post_deploy_execution* table:\n```INSERT INTO `post_deploy_execution` (name, executed_at, execution_time) VALUES ('" . $fileNameWithoutExtension . "', NOW(), " . $executionTime . ");```"
            );
        }
    }
}