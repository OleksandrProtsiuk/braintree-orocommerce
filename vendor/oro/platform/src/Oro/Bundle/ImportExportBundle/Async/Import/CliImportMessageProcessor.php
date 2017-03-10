<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Oro\Bundle\ImportExportBundle\Async\Topics;

class CliImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CliImportHandler
     */
    private $cliImportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CliImportHandler $cliImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ConfigManager $configManager,
        LoggerInterface $logger
    ) {
        $this->cliImportHandler = $cliImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'fileName' => null,
            'notifyEmail' => null,
            'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
            'processorAlias' => null,
            'options' => []
        ], $body);


        if (! $body['processorAlias'] || ! $body['fileName']) {
            $this->logger->critical(
                sprintf('Invalid message'),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('oro:import:cli:%s:%s', $body['processorAlias'], $message->getMessageId()),
            function () use ($body) {
                $this->cliImportHandler->setImportingFileName($body['fileName']);

                $result = $this->cliImportHandler->handleImport(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['inputFormat'],
                    $body['inputFilePrefix'],
                    $body['options']
                );

                $summary = sprintf(
                    'Import from file %s for the %s is completed, success: %s, counts: %d, errors: %d, message: %s',
                    $body['fileName'],
                    $result['success'],
                    $result['counts'],
                    $result['errors'],
                    $result['message']
                );

                $this->logger->info($summary);

                if ($body['notifyEmail']) {
                    $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
                    $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
                    $this->producer->send(
                        NotificationTopics::SEND_NOTIFICATION_EMAIL,
                        [
                            'fromEmail' => $fromEmail,
                            'fromName' => $fromName,
                            'toEmail' => $body['notifyEmail'],
                            'subject' => $result['message'],
                            'body' => $summary
                        ]
                    );
                }

                return $result['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_CLI];
    }
}
