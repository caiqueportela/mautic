<?php

namespace Mautic\LeadBundle\Field\Command;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateCustomFieldCommand extends Command
{
    public function __construct(private BackgroundService $backgroundService, private TranslatorInterface $translator)
    {
        parent::__construct();
    }

    public function configure()
    {
        parent::configure();

        $this->setName('mautic:custom-field:update-column')
            ->setDescription('Create custom field column in the background')
            ->addOption('--id', '-i', InputOption::VALUE_REQUIRED, 'LeadField ID.')
            ->addOption('--user', '-u', InputOption::VALUE_OPTIONAL, 'User ID - User which receives a notification.')
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will create a column in a lead_fields table if the proces should run in background.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $leadFieldId = (int) $input->getOption('id');
        $userId      = (int) $input->getOption('user');

        try {
            $this->backgroundService->updateColumn($leadFieldId, $userId);
        } catch (LeadFieldWasNotFoundException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.notfound').'</error>');

            return 1;
        } catch (CustomFieldLimitException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return 1;
        } catch (AbortColumnUpdateException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.field.column_update_aborted').'</error>');

            return 0;
        } catch (DriverException | SchemaException | DBALException | \Mautic\CoreBundle\Exception\SchemaException $e) {
            $output->writeln('<error>'.$this->translator->trans($e->getMessage()).'</error>');

            return 1;
        }

        $output->writeln('');
        $output->writeln('<info>'.$this->translator->trans('mautic.lead.field.column_was_updated').'</info>');

        return 0;
    }
}
