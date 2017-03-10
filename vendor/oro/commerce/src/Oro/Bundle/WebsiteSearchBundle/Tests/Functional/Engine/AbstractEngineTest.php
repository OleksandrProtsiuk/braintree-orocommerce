<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;

abstract class AbstractEngineTest extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;

    /**
     * @var callable
     */
    protected $listener;

    /**
     * @var AbstractEngine
     */
    protected $engine;

    protected function setUp()
    {
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->listener = $this->getIndexEntityListener();
        $this->getContainer()->get('event_dispatcher')->addListener(
            IndexEntityEvent::NAME,
            $this->listener,
            -255
        );

        $this->engine = $this->getSearchEngine();
    }

    protected function tearDown()
    {
        $this->getContainer()->get('event_dispatcher')->removeListener(IndexEntityEvent::NAME, $this->listener);
    }

    /**
     * @param Query $query
     * @return Result\Item[]
     */
    protected function getSearchItems(Query $query)
    {
        $searchResults = $this->engine->search($query);

        return $searchResults->getElements();
    }

    /**
     * @return callable
     */
    protected function getIndexEntityListener()
    {
        $listener = function (IndexEntityEvent $event) {
            $defaultLocalizationId = $this->getDefaultLocalizationId();

            $items = $this->getContainer()->get('doctrine')
                ->getRepository(TestEntity::class)
                ->findBy(['id' => $event->getEntities()]);

            /** @var TestEntity $item */
            foreach ($items as $item) {
                $event->addField($item->getId(), 'stringValue', $item->stringValue);
                $event->addField($item->getId(), 'integerValue', $item->integerValue);
                $event->addField($item->getId(), 'decimalValue', $item->decimalValue);
                $event->addField($item->getId(), 'floatValue', $item->floatValue);
                $event->addField($item->getId(), 'datetimeValue', $item->datetimeValue);
                $event->addField($item->getId(), 'phone', $item->phone);
                $event->addField($item->getId(), 'blobValue', (string)$item->blobValue);

                $event->addPlaceholderField(
                    $item->getId(),
                    'title_LOCALIZATION_ID',
                    "Some text with placeholder {$defaultLocalizationId} for {$item->stringValue}",
                    [LocalizationIdPlaceholder::NAME => $defaultLocalizationId]
                );
            }
        };

        return $listener;
    }

    public function testRecordUrlForSearchAll()
    {
        $query = new Query();
        $query->from('*');
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);
        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);
        $this->assertStringStartsWith('item1@mail.com', $items[0]->getRecordTitle());
        $this->assertStringStartsWith('item2@mail.com', $items[1]->getRecordTitle());
        $this->assertStringStartsWith('item3@mail.com', $items[2]->getRecordTitle());
        $this->assertStringStartsWith('item4@mail.com', $items[3]->getRecordTitle());
        $this->assertStringStartsWith('item5@mail.com', $items[4]->getRecordTitle());
        $this->assertStringStartsWith('item6@mail.com', $items[5]->getRecordTitle());
        $this->assertStringStartsWith('item7@mail.com', $items[6]->getRecordTitle());
        $this->assertStringStartsWith('item8@mail.com', $items[7]->getRecordTitle());
        $this->assertStringStartsWith('item9@mail.com', $items[8]->getRecordTitle());
    }

    public function testSearchAll()
    {
        $query = new Query();
        $query->from('*');
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);
        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);
    }

    public function testSearchByAliasWithSelect()
    {
        $searchField = 'stringValue';

        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');
        $query->select($searchField);
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);

        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);
        $this->assertSame('item1@mail.com', $this->getSelectData($searchField, $items[0]));
        $this->assertSame('item2@mail.com', $this->getSelectData($searchField, $items[1]));
        $this->assertSame('item3@mail.com', $this->getSelectData($searchField, $items[2]));
        $this->assertSame('item4@mail.com', $this->getSelectData($searchField, $items[3]));
        $this->assertSame('item5@mail.com', $this->getSelectData($searchField, $items[4]));
        $this->assertSame('item6@mail.com', $this->getSelectData($searchField, $items[5]));
        $this->assertSame('item7@mail.com', $this->getSelectData($searchField, $items[6]));
        $this->assertSame('item8@mail.com', $this->getSelectData($searchField, $items[7]));
        $this->assertSame('item9@mail.com', $this->getSelectData($searchField, $items[8]));
    }

    public function testSearchByAliasWithCriteria()
    {
        $expr = new Comparison('integer.integerValue', '=', 5000);
        $criteria = new Criteria($expr);
        $searchField = 'stringValue';

        $query = new Query();
        $query->select($searchField)
            ->from('oro_test_item_WEBSITE_ID')
            ->setCriteria($criteria);

        $items = $this->getSearchItems($query);

        $this->assertCount(1, $items);
        $this->assertSame('item5@mail.com', $this->getSelectData($searchField, $items[0]));
    }

    /**
     * @param string $field
     * @param Item $item
     */
    protected function getSelectData($field, Item $item)
    {
        $selectedData = $item->getSelectedData();

        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(sprintf('Field "%s" not found in selectedData', $field));
        }

        return $selectedData[$field];
    }

    /**
     * @return AbstractEngine
     */
    abstract protected function getSearchEngine();
}
