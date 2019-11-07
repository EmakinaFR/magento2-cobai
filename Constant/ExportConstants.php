<?php

namespace Emakina\CmsImportExport\Constant;

class ExportConstants
{
    //**************** ARCHIVE ****************
    public const ARCHIVE_PATH = self::BASE_PATH . 'Archive/';

    //**************** BASE ****************
    public const BASE_PATH = 'var/Export/';

    //**************** BLOCK ****************
    public const BLOCK_HEADER =
        [
            'title',
            'identifier',
            'content',
            'is_active',
            'store_id',
        ];

    public const BLOCK_PATH = self::BASE_PATH . 'Block/';

    //**************** HIERARCHY ****************
    public const HIERARCHY_HEADER =
        [
            'scope',
            'scope_id',
            'node_id',
            'parent_node_id',
            'page_identifier',
            'page_store',
            'identifier',
            'label',
            'level',
            'sort_order',
            'request_url',
            'xpath',
            'meta_first_last',
            'meta_next_previous',
            'meta_chapter',
            'meta_section',
            'meta_cs_enabled',
            'pager_visibility',
            'pager_frame',
            'pager_jump',
            'menu_visibility',
            'menu_excluded',
            'menu_layout',
            'menu_brief',
            'menu_levels_down',
            'menu_ordered',
            'menu_list_type',
            'top_menu_visibility',
            'top_menu_excluded',
        ];

    public const HIERARCHY_PATH = self::BASE_PATH . 'Hierarchy/';

    //**************** IMAGE ****************
    public const IMAGE_DIRECTORY = 'pub/media/wysiwyg/';

    public const IMAGE_PATH = self::BASE_PATH . 'Image/';

    //**************** PAGE ****************
    public const PAGE_HEADER =
        [
            'title',
            'page_layout',
            'meta_keywords',
            'meta_description',
            'identifier',
            'content_heading',
            'content',
            'is_active',
            'sort_order',
            'layout_update_xml',
            'custom_theme',
            'custom_root_template',
            'custom_layout_update_xml',
            'custom_theme_from',
            'meta_title',
            'website_root',
            'is_searchable',
            'store_id',
        ];

    public const PAGE_PATH = self::BASE_PATH . 'Page/';

    //**************** COMMAND RETURN ****************
    public const COMMAND_ERROR = 1;

    public const COMMAND_OK = 0;
}