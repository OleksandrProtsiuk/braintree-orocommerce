<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\SluggableEntityListener;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SluggableEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var SluggableEntityListener
     */
    protected $sluggableEntityListener;

    protected function setUp()
    {
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sluggableEntityListener = new SluggableEntityListener(
            $this->messageFactory,
            $this->messageProducer,
            $this->configManager
        );
    }

    public function testPostPersistDisabledDirectUrl()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->sluggableEntityListener->postPersist($args);
    }

    public function testPostPersistNotSluggableEntity()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $entity = new \stdClass();
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->sluggableEntityListener->postPersist($args);
    }

    public function testPostPersist()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($entity)
            ->willReturn($message);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITY, $message);

        $this->sluggableEntityListener->postPersist($args);
    }

    public function testOnFlushDisabledDirectUrl()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $prototype = new LocalizedFallbackValue();

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity
            ]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$prototype]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->sluggableEntityListener->onFlush($event);
    }

    public function testOnFlushNoChangedSlugs()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new LocalizedFallbackValue()]);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new LocalizedFallbackValue()]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->sluggableEntityListener->onFlush($event);
    }

    public function testOnFlushChangedSlugWithoutChangedPrototypesUp()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity,
                new LocalizedFallbackValue()
            ]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->sluggableEntityListener->onFlush($event);
    }

    public function testOnFlushChangedSlugWithChangedPrototypesIns()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $prototype = new LocalizedFallbackValue();

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity
            ]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$prototype]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($entity)
            ->willReturn($message);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITY, $message);

        $this->sluggableEntityListener->onFlush($event);
    }

    public function testOnFlushChangedSlugWithChangedPrototypesDel()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(OnFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $prototype = new LocalizedFallbackValue();

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->once())
            ->method('hasSlugPrototype')
            ->with($prototype)
            ->willReturn(true);

        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $entity
            ]);
        $uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$prototype]);

        $message = ['class' => get_class($entity), 'id' => 1];
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($entity)
            ->willReturn($message);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::GENERATE_DIRECT_URL_FOR_ENTITY, $message);

        $this->sluggableEntityListener->onFlush($event);
    }
}
