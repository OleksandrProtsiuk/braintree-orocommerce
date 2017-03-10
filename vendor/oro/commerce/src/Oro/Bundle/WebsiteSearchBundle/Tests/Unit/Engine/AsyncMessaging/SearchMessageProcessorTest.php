<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;

class SearchMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject $indexer
     */
    private $indexer;

    /**
     * @var SearchMessageProcessor
     */
    private $processor;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    public function setUp()
    {
        $this->indexer = $this->createMock(IndexerInterface::class);

        $this->processor = new SearchMessageProcessor($this->indexer);

        $this->session = $this->createMock(SessionInterface::class);
    }

    /**
     * @dataProvider processingMessageDataProvider
     */
    public function testProcessingMessage($messageBody, $topic, $expectedMethod)
    {
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn($topic);

        $this->indexer->expects($this->once())
            ->method($expectedMethod);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testRejectOnUnsupportedTopic()
    {
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode('body')));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @return array
     */
    public function processingMessageDataProvider()
    {
        return [
            'save' => [
                'message' =>[
                    'entity' => [
                        'class' => '\StdClass',
                        'id' => 13
                    ],
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_SAVE,
                'expectedMethod' => 'save'
            ],
            'delete' => [
                'message' =>[
                    'entity' => [
                        'class' => '\StdClass',
                        'id' => 13
                    ],
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_DELETE,
                'expectedMethod' => 'delete'
            ],
            'reindex' => [
                'message' =>[
                    'class' => '\StdClass',
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'resetReindex' => [
                'message' =>[
                    'class' => '\StdClass',
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_RESET_INDEX,
                'expectedMethod' => 'resetIndex'
            ]
        ];
    }
}
