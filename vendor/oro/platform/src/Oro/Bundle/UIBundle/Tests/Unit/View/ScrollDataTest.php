<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\View;

use Oro\Bundle\UIBundle\View\ScrollData;

class ScrollDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScrollData
     */
    protected $scrollData;

    protected function setUp()
    {
        $this->scrollData = new ScrollData();
    }

    public function testSetGetData()
    {
        $this->assertEmpty($this->scrollData->getData());

        $data = ['some' => 'fields'];
        $this->scrollData->setData($data);
        $this->assertAttributeEquals($data, 'data', $this->scrollData);
        $this->assertEquals($data, $this->scrollData->getData());
    }

    /**
     * @param array $expected
     * @param string $title
     * @param int|null null $priority
     * @param string|null $class
     * @param bool $useSubBlockDivider
     * @dataProvider addBlockDataProvider
     */
    public function testAddBlock(array $expected, $title, $priority = null, $class = null, $useSubBlockDivider = true)
    {
        $this->assertEquals(0, $this->scrollData->addBlock($title, $priority, $class, $useSubBlockDivider));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function addBlockDataProvider()
    {
        return [
            'minimum parameters' => [
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        [
                            ScrollData::TITLE => 'test title',
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::SUB_BLOCKS => [],
                        ]
                    ]
                ],
                'title' => 'test title',
            ],
            'maximum parameters' => [
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        [
                            ScrollData::TITLE => 'test title',
                            ScrollData::PRIORITY => 25,
                            ScrollData::BLOCK_CLASS => 'active',
                            ScrollData::USE_SUB_BLOCK_DIVIDER => false,
                            ScrollData::SUB_BLOCKS => [],
                        ]
                    ]
                ],
                'title' => 'test title',
                'priority' => 25,
                'class' => 'active',
                'useSubBlockDivider' => false,
            ],
        ];
    }

    /**
     * @param array $source
     * @param array $expected
     * @param int $blockId
     * @param string|null $title
     * @dataProvider addSubBlockDataProvider
     */
    public function testAddSubBlock(array $source, array $expected, $blockId, $title = null)
    {
        $this->scrollData->setData($source);
        $this->assertEquals(0, $this->scrollData->addSubBlock($blockId, $title));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function addSubBlockDataProvider()
    {
        $source = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [],
                ],
                1 => [
                    ScrollData::TITLE => 'test title 1',
                    ScrollData::SUB_BLOCKS => [],
                ]
            ]
        ];

        $expectedFirst = $source;
        $expectedFirst[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][]
            = [ScrollData::DATA => []];

        $expectedSecond = $source;
        $expectedSecond[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][]
            = [ScrollData::TITLE => 'subblock title', ScrollData::DATA => []];

        return [
            'add to first block' => [
                'source' => $source,
                'expected' => $expectedFirst,
                'blockId' => 0,
            ],
            'add to second block' => [
                'source' => $source,
                'expected' => $expectedSecond,
                'blockId' => 1,
                'title' => 'subblock title'
            ],
        ];
    }

    /**
     * @return array
     */
    public function addSubBlockDataDataProvider()
    {
        $initialData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => ['some data']
                        ],
                    ],
                ],
            ]
        ];

        $html = 'another data';
        $expectedFieldNameData = $expectedData = $initialData;
        $expectedData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][] = $html;

        $fieldName = 'someFieldName';
        $expectedFieldNameData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][$fieldName]
            = $html;

        return [
            [
                'html' => $html,
                'fieldName' => null,
                'expectedId' => 1,
                'initialData' => $initialData,
                'expectedData' => $expectedData
            ],
            [
                'html' => $html,
                'fieldName' => $fieldName,
                'expectedId' => $fieldName,
                'initialData' => $initialData,
                'expectedData' => $expectedFieldNameData
            ]
        ];
    }

    /**
     * @dataProvider addSubBlockDataDataProvider
     * @param string $html
     * @param string|null $fieldName
     * @param int|string $expectedId
     * @param array $initialData
     * @param array $expectedData
     */
    public function testAddSubBlockData($html, $fieldName, $expectedId, array $initialData, array $expectedData)
    {
        $this->scrollData->setData($initialData);
        $this->assertEquals($expectedId, $this->scrollData->addSubBlockData(0, 0, $html, $fieldName));
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block 0 is not defined
     */
    public function testAddSubBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlock(0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block 0 is not defined
     */
    public function testAddSubBlockDataNoBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Subblock 0 is not defined
     */
    public function testAddSubBlockDataNoSubBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => [0 => [ScrollData::SUB_BLOCKS => []]]]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    /**
     * @return array
     */
    public function addNamedBlockDataProvider()
    {
        $someBlock = [
            'title' => 'SomeBlock title',
            'useSubBlockDivider' => true,
            'priority' => 1,
            'class' => 'SomeClass',
            'subblocks' => []
        ];

        $newBlock = [
            'title' => 'NewBlock title',
            'useSubBlockDivider' => true,
            'priority' => 55,
            'class' => 'Class',
            'subblocks' => []
        ];

        return [
            'add new block' => [
                'blockName' => 'NewBlock',
                'title' => 'NewBlock title',
                'priority' => 55,
                'class' => 'Class',
                'useDivider' => true,
                'initialData' => [
                    'dataBlocks' => [
                        $someBlock,
                    ]
                ],
                'expectedData' => [
                    'dataBlocks' => [
                        $someBlock,
                        'NewBlock' => $newBlock
                    ]
                ]
            ],
            'update existing block' => [
                'blockName' => 'NewBlock',
                'title' => 'NewBlock title',
                'priority' => 55,
                'class' => 'Class',
                'useDivider' => true,
                'initialData' => [
                    'dataBlocks' => [
                        'NewBlock' => [
                            'title' => 'OldBlock title',
                            'useSubBlockDivider' => false,
                            'priority' => 77,
                            'class' => 'Old Class',
                            'subblocks' => []
                        ]
                    ]
                ],
                'expectedData' => [
                    'dataBlocks' => [
                        'NewBlock' => $newBlock
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider addNamedBlockDataProvider
     * @param string $blockName
     * @param mixed|string $title
     * @param int|null $priority
     * @param string|null $class
     * @param bool $useDivider
     * @param array $initialData
     * @param array $expectedData
     */
    public function testAddNamedBlock(
        $blockName,
        $title,
        $priority,
        $class,
        $useDivider,
        array $initialData,
        array $expectedData
    ) {
        $this->scrollData->setData($initialData);
        $this->scrollData->addNamedBlock($blockName, $title, $priority, $class, $useDivider);

        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function removeNamedBlockDataProvider()
    {
        $newBlock = [
            'title' => 'OldBlock title',
            'useSubBlockDivider' => false,
            'priority' => 77,
            'class' => 'Old Class',
            'subblocks' => []
        ] ;

        return [
            'remove not existing block' => [
                'blockName' => 'NotExistingBlock',
                'initialData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ],
                'expectedData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ]
            ],
            'remove existing block' => [
                'blockName' => 'NewBlock',
                'initialData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ],
                'expectedData' => [
                    'dataBlocks' => []
                ]
            ]
        ];
    }

    /**
     * @dataProvider removeNamedBlockDataProvider
     * @param string $blockName
     * @param array $initialData
     * @param array $expectedData
     */
    public function testRemoveNamedBlock($blockName, array $initialData, array $expectedData)
    {
        $this->scrollData->setData($initialData);
        $this->scrollData->removeNamedBlock($blockName);
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function hasNamedFieldDataProvider()
    {
        $blockData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'another data',
                                'someFieldName' => 'some data'
                            ]
                        ],
                    ],
                ],
            ]
        ];

        return [
            [
                'blockData' => $blockData,
                'fieldName' => 'notExisting',
                'isExistingBlock' => false
            ],
            [
                'blockData' => $blockData,
                'fieldName' => 'someFieldName',
                'isExistingBlock' => true
            ],
        ];
    }

    /**
     * @dataProvider hasNamedFieldDataProvider
     * @param array $blockData
     * @param string $fieldName
     * @param bool $isExistingBlock
     */
    public function testHasNamedField(array $blockData, $fieldName, $isExistingBlock)
    {
        $this->scrollData->setData($blockData);
        $this->assertEquals($isExistingBlock, $this->scrollData->hasNamedField($fieldName));
    }

    /**
     * @return array
     */
    public function moveFieldToBlockDataProvider()
    {
        $blockData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'another data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'someFieldName' => 'some data'
                            ]
                        ],
                    ],
                ],
                1 => [
                    ScrollData::TITLE => 'test title 1',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $expectedData = $blockData;
        unset($expectedData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][1][ScrollData::DATA]['someFieldName']);
        $expectedData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['someFieldName']
            = 'some data';

        return [
            'move field to not existing block' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 3,
                'fieldId' => 'someFieldName'
            ],
            'move not existing field' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 0,
                'fieldId' => 'notExistingField'
            ],
            'move field to same block' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 0,
                'fieldId' => 'someFieldName'
            ],
            'move field to another block' => [
                'blocksData' => $blockData,
                'expectedData' => $expectedData,
                'blockId' => 1,
                'fieldId' => 'someFieldName'
            ],
        ];
    }

    /**
     * @dataProvider moveFieldToBlockDataProvider
     * @param array $blocksData
     * @param array $expectedData
     * @param string $blockId
     * @param string $fieldId
     */
    public function testMoveFieldToBlock(array $blocksData, array $expectedData, $blockId, $fieldId)
    {
        $this->scrollData->setData($blocksData);
        $this->scrollData->moveFieldToBlock($fieldId, $blockId);
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    public function testGetBlockIdsWhenBlocksAreEmpty()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => []
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([], $this->scrollData->getBlockIds());
    }

    public function testGetBlockIdsWhenBlocksAreNotEmpty()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => []
                ],
                'namedBlock' => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => []
                ],
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([0, 'namedBlock'], $this->scrollData->getBlockIds());
    }

    public function testGetSubblockIdsWhenBlockNotExists()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => []
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([], $this->scrollData->getBlockIds());
    }

    public function testGetSubblockIdsWhenBlockExists()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                5 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => ['some data']
                        ],
                        'namedSubblock' => [
                            ScrollData::DATA => ['some data']
                        ],
                    ]
                ],
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([0, 'namedSubblock'], $this->scrollData->getSubblockIds(5));
    }
}
