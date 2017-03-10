<?php

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:website-search:reindex';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(e.g. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addOption(
                'website-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'INTEGER. Website ID needs to reindex'
            )
            ->setDescription('Rebuild search index for certain website/entity type or all mapped entities');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');
        $websiteId = $input->getOption('website-id');

        $class = $class ? $this->getFQCN($class) : null;
        
        $classes = $class ? [$class] : [];
        $websiteIds = $websiteId ? [(int)$websiteId] : [];

        $output->writeln($this->getStartingMessage($class, $websiteId));

        $event = new ReindexationRequestEvent($classes, $websiteIds, [], false);
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);

        $output->writeln('Reindex finished successfully.');
    }

    /**
     * @param string|null $class
     * @param int $websiteId
     * @return string
     */
    private function getStartingMessage($class, $websiteId)
    {
        $websitePlaceholder = $websiteId ? sprintf(' and website ID %d', $websiteId) : '';

        return sprintf(
            'Starting reindex task for %s%s...',
            $class ?: 'all mapped entities',
            $websitePlaceholder
        );
    }

    /**
     * @param string $class
     * @return string
     */
    private function getFQCN($class)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($class)
            ->getClassMetadata($class)
            ->getName();
    }
}
