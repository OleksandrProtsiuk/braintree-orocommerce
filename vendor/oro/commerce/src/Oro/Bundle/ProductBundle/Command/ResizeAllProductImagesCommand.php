<?php

namespace Oro\Bundle\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ResizeAllProductImagesCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'product:image:resize-all';
    const OPTION_FORCE = 'force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addOption(self::OPTION_FORCE, null, null, 'Overwrite existing images')
            ->setDescription(<<<DESC
Resize All Product Images (the command only adds jobs to a queue, ensure the oro:message-queue:consume command 
is running to get images resized)
DESC
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $forceOption = (bool) $input->getOption(self::OPTION_FORCE);

        $container = $this->getContainer();
        $productImageClass = $container->getParameter('oro_product.entity.product_image.class');

        /** @var ProductImage[] $productImages */
        $productImages = $container
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass($productImageClass)
            ->findAll();

        if (!$productImageCount = count($productImages)) {
            $output->writeln('No product images found.');

            return;
        }

        $eventDispatcher = $container->get('event_dispatcher');
        foreach ($productImages as $productImage) {
            $eventDispatcher->dispatch(
                ProductImageResizeEvent::NAME,
                new ProductImageResizeEvent($productImage, $forceOption)
            );
        }
        $output->writeln(sprintf('%d product images successfully queued for resize.', $productImageCount));
    }
}
