<?php

declare(strict_types=1);

namespace App\Workspace\UI\Cli;

use App\Workspace\Application\Command\ImportHrnestPeople\ImportHrnestPeopleCommand as ImportHrnestPeopleApplicationCommand;
use App\Workspace\Application\Command\ImportHrnestPeople\ImportHrnestPeopleHandler;
use App\Workspace\Infrastructure\Hrnest\HrnestApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:hrnest:import-people',
    description: 'Pobiera wszystkie osoby z HRnest i synchronizuje je z lokalna baza, opcjonalnie z przypisaniem do biurek.',
)]
final class ImportHrnestPeopleCommand extends Command
{
    public function __construct(
        private readonly ImportHrnestPeopleHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Pobiera i mapuje dane bez zapisu do bazy.')
            ->addOption('people-path', null, InputOption::VALUE_REQUIRED, 'Nadpisuje sciezke endpointu listy osob.')
            ->addOption('desk-field', null, InputOption::VALUE_REQUIRED, 'Dot-path do pola z identyfikatorem biurka, np. custom_fields.desk_id.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $result = $this->handler->handle(new ImportHrnestPeopleApplicationCommand(
                $input->getOption('dry-run'),
                $this->normalizeOptionalString($input->getOption('people-path')),
                $this->normalizeOptionalString($input->getOption('desk-field')),
            ));
        } catch (HrnestApiException $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $io->success(sprintf(
            'HRnest import zakonczony. Pobrano %d osob, utworzono %d, zaktualizowano %d, przypisano %d biurek%s.',
            $result->fetchedCount,
            $result->createdCount,
            $result->updatedCount,
            $result->deskAssignedCount,
            $input->getOption('dry-run') ? ' (dry-run, bez zapisu)' : '',
        ));

        if ($result->warnings !== []) {
            $io->warning('Import zakonczyl sie z ostrzezeniami:');

            foreach ($result->warnings as $warning) {
                $io->writeln('- '.$warning);
            }
        }

        return self::SUCCESS;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
