<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

class CombinedPriceListScheduleCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:price-lists:schedule';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Prepare and activate combined price list by schedule');
    }

    public function isActive()
    {
        $container = $this->getContainer();
        $offsetHours = $container->get('oro_config.manager')
            ->get('oro_pricing.offset_of_processing_cpl_prices');

        $count = $container->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffsetCount($offsetHours);

        return ($count > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $container->get('oro_pricing.resolver.combined_product_schedule_resolver')->updateRelations();
        $this->combinePricesForScheduledCPL();
    }

    protected function combinePricesForScheduledCPL()
    {
        $container = $this->getContainer();
        $offsetHours = $this->getContainer()->get('oro_config.manager')
            ->get('oro_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $container->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $combinedProductPriceResolver = $container->get('oro_pricing.resolver.combined_product_price_resolver');

        foreach ($combinedPriceLists as $combinedPriceList) {
            $combinedProductPriceResolver->combinePrices($combinedPriceList);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
