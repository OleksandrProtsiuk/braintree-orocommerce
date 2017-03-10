UPGRADE FROM 1.10 to 2.0
========================

####General
- **Upgrade to 2.0 is available only from 1.10 version**.

  To correctly upgrade to version 2.0 follow the steps in the guide [How to Upgrade to a New Version](https://www.orocrm.com/documentation/index/current/cookbook/how-to-upgrade-to-new-version).
  At **Step 7** instead of running
  ```shell
  $ sudo -u www-data php app/console oro:platform:update --env=prod --force
  ```
  you will run **only once** the upgrade command introduced to help upgrading from 1.10 to 2.0
  ```shell
  $ sudo -u www-data php app/console oro:platform:upgrade20 --env=prod --force
  ```
  
  Upgrade from version less then 1.10 is not supported.
- Changed minimum required php version to 5.6
- Field `dataChannel` for `Lead` and `Opportunity` was removed. To keep BC after upgrade to 2.0 and keep data in reports and data grids this field is converted in extend field with name `data_channel`.

###SOAP API was removed
- removed all dependencies to the `besimple/soap-bundle` bundle.
- removed SOAP annotations from the entities. Updated entities:
    - Oro\Bundle\AccountBundle\Entity\Account
    - Oro\Bundle\ContactBundle\Entity\Contact
    - Oro\Bundle\ContactBundle\Entity\ContactAddress
    - Oro\Bundle\ContactBundle\Entity\ContactEmail
    - Oro\Bundle\ContactBundle\Entity\ContactPhone
    - Oro\Bundle\ContactBundle\Entity\Group
    - Oro\Bundle\ContactBundle\Entity\Method
    - Oro\Bundle\ContactBundle\Entity\Source
    - Oro\Bundle\MagentoBundle\Entity\Address
    - Oro\Bundle\MagentoBundle\Entity\CartAddress
    - Oro\Bundle\MagentoBundle\Entity\OrderAddress
    - Oro\Bundle\SalesBundle\Entity\B2bCustomerEmail
    - Oro\Bundle\SalesBundle\Entity\LeadAddress
    - Oro\Bundle\SalesBundle\Entity\LeadEmail
- removed classes:
    - Oro\Bundle\AccountBundle\Controller\Api\Soap\AccountController
    - Oro\Bundle\ContactBundle\Controller\Api\Soap\ContactController
    - Oro\Bundle\ContactBundle\Controller\Api\Soap\ContactGroupController
    - Oro\Bundle\SearchBundle\Controller\Api\SoapController
    - Oro\Bundle\CaseBundle\Entity\CaseCommentSoap
    - Oro\Bundle\CaseBundle\Entity\CaseEntitySoap
    - Oro\Bundle\AccountBundle\Tests\Functional\API\SoapAccountTest
    - Oro\Bundle\CaseBundle\Tests\Functional\Controller\Api\Soap\CaseControllerTest
    - Oro\Bundle\CaseBundle\Tests\Functional\Controller\Api\Soap\CommentControllerTest
    - Oro\Bundle\ContactBundle\Tests\Functional\API\SoapContactApiTest
    - Oro\Bundle\ContactBundle\Tests\Functional\API\SoapContactGroupApiTest

####OroSalesBundle:
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\SalesBundle\Entity\Lead`
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\SalesBundle\Entity\Opportunity`
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\SalesBundle\Entity\SalesFunnel`
- `OroCRM/Bundle/SalesBundle/Entity/LeadMailboxProcessSettings` extends `Oro\Bundle\SalesBundle\Model\ExtendLeadMailboxProcessSettings`
- Class `Oro\Bundle\SalesBundle\Provider\LeadToOpportunityProvider`
    - construction signature was changed, now it takes the next arguments:
        - `B2bGuesser` $b2bGuesser,
        - `EntityFieldProvider` $entityFieldProvider,
        - `ChangeLeadStatus` $changeLeadStatus
    - method `isDisqualifyAndConvertAllowed` was removed. Use methods `Oro\Bundle\SalesBundle\Provider\LeadActionsAccessProvider::isDisqualifyAllowed` and `Oro\Bundle\SalesBundle\Provider\LeadActionsAccessProvider::isConvertToOpportunityAllowed` instead.
- Changed signature of constructor of `Oro\Bundle\SalesBundle\Form\Type\LeadType` - now it takes the following argument:
      - `EntityAliasResolver $entityAliasResolver`.
- Changed signature of constructor of `Oro\Bundle\SalesBundle\Form\Type\OpportunityType` - now it takes the following arguments:
      - `ProbabilityProvider $probabilityProvider`,
        `EnumValueProvider $enumValueProvider`,
        `EnumTypeHelper $typeHelper`,
        `OpportunityRelationsBuilder $relationsBuilder`,
        `EntityAliasResolver $entityAliasResolver`.
- Changed signature of constructor of `Oro\Bundle\SalesBundle\Provider\LeadToOpportunityProvider` - now it takes the following arguments:
      - `EntityFieldProvider $entityFieldProvider`,
        `ChangeLeadStatus $changeLeadStatus`
- Service (`Oro\Bundle\SalesBundle\Model\B2bGuesser`) removed
- Guesser (`Oro\Bundle\ChannelBundle\Form\Guesser\ChannelTypeGuesser`) removed
- `Lead` and `Opportunity` entities do not implement `ChannelAwareInterface`
- `ChannelEntityTrait` was removed from `Lead` and `Opportunity` entities
- Type (`Oro\Bundle\SalesBundle\Form\Type\LeadDataChannelAwareSelectType`) is removed
- Type (`Oro\Bundle\SalesBundle\Form\Type\OpportunityDataChannelAwareSelectType`) is removed
- For the type (`Oro\Bundle\SalesBundle\Form\Type\LeadSelectType`) was changed parent from `oro_entity_create_or_select_inline_channel_aware` to `oro_entity_create_or_select_inline`
- For the type (`Oro\Bundle\SalesBundle\Form\Type\OpportunitySelectType`) was changed parent from `oro_entity_create_or_select_inline_channel_aware` to `oro_entity_create_or_select_inline`
- Data girds `sales-funnel-lead-with-data-channel-grid` and `sales-funnel-opportunity-with-data-channel-grid` were removed
- Validation NotBlank for field `dataChannel` of entities `Oro\Bundle\ContactUsBundle\Entity\ContactRequest, Oro\Bundle\SalesBundle\Entity\Opportunity, Oro\Bundle\SalesBundle\Entity\Lead` was removed
- Configurations of data grids `sales-opportunity-for-context-grid, sales-lead-for-context-grid, sales-lead-grid, sales-opportunity-grid` were updated. There were deleted configurations channelName in the sections columns, filters, sorters.
- Changed signature of constructor of `Oro\Bundle\SalesBundle\Datagrid\Extension\Customers\AccountExtension`. The argument `GridConfigurationHelper $gridConfigurationHelper` was replaces with `EntityClassResolver $entityClassResolver`.

####OroCaseBundle:
- `OroCRM/Bundle/CaseBundle/Entity/CaseMailboxProcessSettings` extends `Oro\Bundle\CaseBundle\Model\ExtendCaseMailboxProcessSettings`

####OroContactUsBundle:
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\ContactUsBundle\Entity\ContactRequest`
- Removed implementation `ChannelAwareInterface` in `Oro/Bundle/ContactUsBundle/Entity/ContactRequest`

####OroMagentoBundle:
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\MagentoBundle\Entity\Cart`
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\MagentoBundle\Entity\Order`
- The `Oro\Bundle\MagentoBundle\Provider\CartExpirationProcessor` now implements `Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface`
- The class `Oro\Bundle\MagentoBundle\Command\CartExpirationSyncCommand` renamed to `Oro\Bundle\MagentoBundle\Command\SyncCartExpirationCommand`.
- The `Oro\Bundle\MagentoBundle\Command\InitialSyncCommand` command was removed.
- Removed constant `SELECT_PATH` from `Oro\Bundle\MagentoBundle\EventListener\OrderGridListener`

####OroChannelBundle:
- The event `orocrm_channel.channel.status_change` was removed. Use the message queue topic `oro.channel.channel_status_changed` instead.
- The class `Oro\Bundle\ChannelBundle\EventListener\ChangeIntegrationStatusListener` was removed.
- The class `Oro\Bundle\ChannelBundle\Event\ChannelChangeStatusEvent` was removed.
- The parameter `orocrm_channel.event_listener.change_integration_status.class` was removed.
- The parameter `orocrm_channel.event_listener.timezone_change.class` was removed.
- Channel configuration file now loads from `Resources/config/oro/channels.yml` instead of `Resources/config/channel_configuration.yml`.
- Root node for channel config in `Resources/config/oro/channels.yml` were changed from `orocrm_channel` to `channels`.
- Added channel repository interface `Oro\Bundle\ChannelBundle\Entity\Repository\ChannelRepositoryInterface`.
- Added channel repository abstract `Oro\Bundle\ChannelBundle\Entity\Repository\ChannelRepositoryAbstract`.
- Deprecated `getVisitsCountByPeriodForChannelType()` method in `Oro\Bundle\ChannelBundle\Entity\Repository\ChannelRepositoryInterface`.
- The interface `Oro/Bundle/ChannelBundle/Model/CustomerIdentityInterfaceCustomerIdentityInterface` was removed.
- Using active/inactive status for channel terminated.

###OroMarketingListBundle
- Class `Oro\Bundle\MarketingListBundle\Model\ContactInformationFieldHelper`:
    - method `getQueryContactInformationColumns` was removed. Use method `getQueryContactInformationFields` instead.
    - method `getEntityContactInformationColumns` `was removed. Use method getEntityContactInformationFields` instead.
    - method `getEntityContactInformationColumnsInfo` was removed. Use method `getEntityContactInformationFieldsInfo` instead.
    - method `getEntityLevelContactInfoColumns` was removed. Use method `getEntityLevelContactInfoFields` instead.

####OroAnalyticsBundle:
- The class `Oro\Bundle\AnalyticsBundle\Model\StateManager` and its service `orocrm_analytics.model.state_manager` were removed.
- The method `scheduleRecalculation` of `Oro\Bundle\AnalyticsBundle\Model\RFMMetricStateManager` was removed. Use appropriate method from `Oro\Bundle\AnalyticsBundle\Service\CalculateAnalyticsScheduler` service.


####CalendarCRMBridgeBundle:
- CalendarCRMBridgeBundle was added to integrate OroCalendarBundle into CRM

####OroCampaignBundle
- Moved ``CampaignBundle`` to a separate ``marketing`` package, required by default in the CRM application.

####OroMarketingListBundle
- Moved ``MarketingListBundle`` to a separate ``marketing`` package, required by default in the CRM application.

####OroDataGridBundle:
- New ACL Capability "Export Grid View" added under "Application" Category to control Export Grid action.

####OroSecurityBundle:
- Added fixture data loader `Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData` to provide easy way for setting default ACLs for Roles

####ContactBundle:
 - Constructor of `Oro\Bundle\ContactBundle\EventListener\PrepareResultItemListener` was changed. Added `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` as last argument.

####ReportCRMBundle
- The command `oro:report:update` and its class `Oro\Bundle\ReportCRMBundle\Command\ReportUpdateCommand` were removed. 