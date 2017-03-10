<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * AppendToFolderFieldType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class AppendToFolderFieldType extends FolderChangeDescriptionType
{
    /**
     * @var FolderType
     * @access public
     */
    public $Folder;

    /**
     * @var CalendarFolderType
     * @access public
     */
    public $CalendarFolder;

    /**
     * @var ContactsFolderType
     * @access public
     */
    public $ContactsFolder;

    /**
     * @var SearchFolderType
     * @access public
     */
    public $SearchFolder;

    /**
     * @var TasksFolderType
     * @access public
     */
    public $TasksFolder;
}
// @codingStandardsIgnoreEnd
