<?php
namespace Oro\Bundle\MagentoBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AnalyticsBundle\Service\CalculateAnalyticsScheduler;

use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\MagentoBundle\Async\SyncInitialIntegrationProcessor;
use Oro\Bundle\MagentoBundle\Async\Topics;
use Oro\Bundle\MagentoBundle\Provider\InitialSyncProcessor;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

/**
 * @dbIsolationPerTest
 */
class SyncInitialIntegrationProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        self::assertClassImplements(MessageProcessorInterface::class, SyncInitialIntegrationProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        self::assertClassImplements(TopicSubscriberInterface::class, SyncInitialIntegrationProcessor::class);
    }

    public function testShouldSubscribeOnSyncInitialIntegrationTopic()
    {
        $this->assertEquals([Topics::SYNC_INITIAL_INTEGRATION], SyncInitialIntegrationProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new SyncInitialIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub(),
            $this->createCalculateAnalyticsSchedulerMock(),
            new JobRunner(),
            $this->createIndexerInterfaceMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldLogAndRejectIfMessageBodyMissIntegrationId()
    {
        $message = new NullMessage();
        $message->setBody('[]');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The message invalid. It must have integrationId set', ['message' => $message])
        ;

        $processor = new SyncInitialIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub(),
            $this->createCalculateAnalyticsSchedulerMock(),
            new JobRunner(),
            $this->createIndexerInterfaceMock(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The malformed json given.
     */
    public function testThrowIfMessageBodyInvalidJson()
    {
        $processor = new SyncInitialIntegrationProcessor(
            $this->createDoctrineHelperStub(),
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub(),
            $this->createCalculateAnalyticsSchedulerMock(),
            new JobRunner(),
            $this->createIndexerInterfaceMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody('[}');

        $processor->process($message, new NullSession());
    }

    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $registryStub = $this->createDoctrineHelperStub(null);

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Integration not found: theIntegrationId', ['message' => $message])
        ;

        $processor = new SyncInitialIntegrationProcessor(
            $registryStub,
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub([]),
            $this->createCalculateAnalyticsSchedulerMock(),
            new JobRunner(),
            $this->createIndexerInterfaceMock(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $registryStub = $this->createDoctrineHelperStub($integration);

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Integration is not enabled: theIntegrationId', ['message' => $message])
        ;

        $processor = new SyncInitialIntegrationProcessor(
            $registryStub,
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub([]),
            $this->createCalculateAnalyticsSchedulerMock(),
            new JobRunner(),
            $this->createIndexerInterfaceMock(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldAckMessageIfInitialSyncProcessorProcessMessageSuccessfully()
    {
        $integration = new Integration();
        $integration->setEnabled(true);

        $channel = new Channel();

        $registryStub = $this->createDoctrineHelperStub($integration, $channel);
        $jobRunner = new JobRunner();

        $initialSyncProcessorMock = $this->createInitialSyncProcessorMock();
        $initialSyncProcessorMock
            ->expects(self::once())
            ->method('process')
            ->with(
                self::identicalTo($integration),
                'theConnector',
                ['foo' => 'fooVal']
            )
            ->willReturn(true)
        ;

        $processor = new SyncInitialIntegrationProcessor(
            $registryStub,
            $initialSyncProcessorMock,
            $this->createOptionalListenerManagerStub([]),
            $this->createCalculateAnalyticsSchedulerMock(),
            $jobRunner,
            $this->createIndexerInterfaceMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnector',
            'connector_parameters' => ['foo' => 'fooVal'],
        ]));
        $message->setMessageId('theMessageId');

        $result = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageIfInitialSyncProcessorProcessMessageFailed()
    {
        $integration = new Integration();
        $integration->setEnabled(true);

        $channel = new Channel();

        $registryStub = $this->createDoctrineHelperStub($integration, $channel);
        $jobRunner = new JobRunner();

        $initialSyncProcessorMock = $this->createInitialSyncProcessorMock();
        $initialSyncProcessorMock
            ->expects(self::once())
            ->method('process')
            ->with(
                self::identicalTo($integration),
                'theConnector',
                ['foo' => 'fooVal']
            )
            ->willReturn(false)
        ;

        $processor = new SyncInitialIntegrationProcessor(
            $registryStub,
            $initialSyncProcessorMock,
            $this->createOptionalListenerManagerStub([]),
            $this->createCalculateAnalyticsSchedulerMock(),
            $jobRunner,
            $this->createIndexerInterfaceMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode([
            'integration_id' => 'theIntegrationId',
            'connector' => 'theConnector',
            'connector_parameters' => ['foo' => 'fooVal'],
        ]));
        $message->setMessageId('theMessageId');

        $result = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunSyncAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);

        $channel = new Channel();

        $registryStub = $this->createDoctrineHelperStub($integration, $channel);
        $jobRunner = new JobRunner();

        $processor = new SyncInitialIntegrationProcessor(
            $registryStub,
            $this->createInitialSyncProcessorMock(),
            $this->createOptionalListenerManagerStub([]),
            $this->createCalculateAnalyticsSchedulerMock(),
            $jobRunner,
            $this->createIndexerInterfaceMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('orocrm_magento:sync_initial_integration:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InitialSyncProcessor
     */
    private function createInitialSyncProcessorMock()
    {
        $initialProcessor = $this->createMock(InitialSyncProcessor::class);
        $initialProcessor
            ->expects($this->any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy())
        ;

        return $initialProcessor;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OptionalListenerManager
     */
    private function createOptionalListenerManagerStub($listeners = null)
    {
        $managerMock = $this->createMock(OptionalListenerManager::class);
        $managerMock
            ->expects(self::any())
            ->method('getListeners')
            ->willReturn($listeners)
        ;

        return $managerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CalculateAnalyticsScheduler
     */
    private function createCalculateAnalyticsSchedulerMock()
    {
        return $this->createMock(CalculateAnalyticsScheduler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock)
        ;

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($integration = null, $channel = null)
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::any())
            ->method('find')
            ->with(Integration::class)
            ->willReturn($integration)
        ;

        $entityRepositoryMock = $this->createMock(EntityRepository::class);
        $entityRepositoryMock
            ->expects(self::any())
            ->method('findOneBy')
            ->willReturn($channel)
        ;

        $helperMock = $this->createMock(DoctrineHelper::class);
        $helperMock
            ->expects($this->any())
            ->method('getEntityManager')
            ->with(Integration::class)
            ->willReturn($entityManagerMock)
        ;
        $helperMock
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(Channel::class)
            ->willReturn($entityRepositoryMock)
        ;

        return $helperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IndexerInterface
     */
    private function createIndexerInterfaceMock()
    {
        return $this->createMock(IndexerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
