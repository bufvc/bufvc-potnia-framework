<?php
// $Id$
// Hermes DataSource
// Phil Hansen, 03 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Data source for hermes
class HermesDataSource
    extends DataSource
    {
    function HermesDataSource($pear_db)
        {
        global $CONF, $MODULE;
        $config = Array(
            // AV titles
            'title' => Array(
                'title'=>'AV Titles',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'pear_db'=>$pear_db,
                'mysql_table'=>'Title',
                'query_criteria' => Array(
                    Array(
                        'name' => 'q',
                        'qs_key'=> Array('q','adv'),
                        'label' => 'Search for',
                        'render_label' => "Search $MODULE->title for",
                        'index' => 'default',
                        'render_default' => $CONF['search_prompt'],
                        'list' => 'list_search',
                        'advanced_value_count' => 3,
                        'is_primary' => TRUE, // means that it can't be removed and shows up if default
                        ), // query
                    Array(
                        'name' => 'date',
                        'label' => 'Year',
                        'type' => QC_TYPE_DATE_RANGE,
                        'range' => Array( 1896, 2010 ),
                        'add_lists' => TRUE,
                        ), // query
                    Array(
                        'name' =>'title_format',
                        'label' => 'Medium',
                        'list' => 'title_format',
                        'type' => QC_TYPE_FLAG,
                        ),
                    Array(
                        'name' => 'category',
                        'label' => 'Subject',
                        'type' => QC_TYPE_LIST,
                        'render_default' => 'All subjects',
                        'use_integer_list_keys' => TRUE,
                        'list' => 'category',
                        ),
                    Array(
                        'name' => 'country',
                        'label' => 'Country',
                        'type' => QC_TYPE_LIST,
                        'render_default' => 'All countries',
                        'list' => 'country',
                        ),
                    Array(
                        'name' => 'language',
                        'label' => 'Language',
                        'type' => QC_TYPE_LIST,
                        'render_default' => 'All languages',
                        'list' => 'language',
                        ),
                    Array(
                        'name' =>'viewfinder',
                        'label' => 'Viewfinder',
                        'type' => QC_TYPE_FLAG,
                        'mode' => QC_MODE_ADVANCED,
                        'relation' => QC_RELATION_GTE,
                        ),
                    Array(
                        'name' => 'sort',
                        'label' => 'Sort by',
                        'type' => QC_TYPE_SORT,
                        'list' => Array(''=>'Date (oldest first)', 'date_desc'=>'Date (newest first)', 'title'=>'Title', 'relevance'=>'Relevance'),
                        'is_renderable' => FALSE,
                        ),
                    Array(
                        'name' => 'page_size',
                        'label' => 'Display',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        'default' => 10
                        ),
                    Array(
                        'name' =>'facet_media_type',
                        'label' => 'Media types',
                        'type' => QC_TYPE_FLAG,
                        'is_renderable' => FALSE, // used for facet search
                        ),
                    Array(
                        'name' =>'facet_availability',
                        'label' => 'Availability',
                        'type' => QC_TYPE_FLAG,
                        'is_renderable' => FALSE, // used for facet search
                        ),
                    Array(
                        'name' => 'facet_genre',
                        'label' => 'Genre',
                        'type' => QC_TYPE_FLAG,
                        'is_renderable' => FALSE, // used for facet search
                        ),
                    ),
                'query_lists'=> Array(
                    'list_search' => Array(
                        '' => 'All fields',
                        'title' => 'Title',
                        'person' => 'Contributors',
                        'keyword' => 'Keywords',
                        'org' => 'Organisations',
                        ),
                    ),
                'facets' => Array(
                    FACET_OTHER => Array('type'=>'facet_genre', 'name'=>'other', 'select'=>'all'),
                    FACET_30 => Array('type'=>'facet_availability', 'name'=>'30', 'select'=>"is_online=1"),
                    FACET_20 => Array('type'=>'facet_availability', 'name'=>'20', 'select'=>"is_online=0"),
                    FACET_MOVING_IMAGE => Array('type'=>'facet_media_type', 'name'=>'moving_image', 'select'=>"format_summary & 1 AND is_online=1"),
                    FACET_AUDIO => Array('type'=>'facet_media_type', 'name'=>'audio', 'select'=>"format_summary & 2 AND is_online=1"),
                    ),
                'adaptor' => Array(
                    'other' => Array(
                        'index' => 'facet_genre',
                        'value' => 'other',
                        'query_string'=> ''),
                    'avail_30' => Array(
                        'index' => 'facet_availability',
                        'value' => '30',
                        'query_string' => '{is_online=1}'),
                    'avail_20' => Array(
                        'index' => 'facet_availability',
                        'value' => '20',
                        'query_string' => '{is_online=0}'),
                    'moving_image' => Array(
                        'index' => 'facet_media_type',
                        'value' => 'moving_image',
                        'query_string'=> '{format_summary&1}{is_online=1}'),
                    'audio' => Array(
                        'index' => 'facet_media_type',
                        'value' => 'audio',
                        'query_string'=> '{format_summary&2}{is_online=1}'),
                    ),
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'hermes_id',
                        ),
                    // Most tables will have a title field
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        'dc_element'=>'title',
                        'text_label'=>'Title',
                        'bibtex_element'=>'title'
                        ),
                    'subtitle' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'text_label'=>'Sub title',
                        ),
                    'alt_title' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'text_label'=>'Alt title',
                        ),
                    'title_series' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'text_label'=>'Series title',
                        'bibtex_element'=>'series',
                        ),
                    'description' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'dc_element'=>'description',
                        'text_label'=>'Description',
                        'bibtex_element'=>'abstract'
                        ),

                    // Technical information
                    'language' => Array(
                        'type'=>'many_to_one',
                        'select' => 'Language.title AS language, language_id',
                        'join' => 'LEFT JOIN Language ON language_id=Language.id',
                        'dc_element'=>'language',
                        'text_label'=>'Language',
                        'bibtex_element'=>'language'
                        ),
                    'language_id' => Array(
                        'type'=>'varchar',
                        'size'=>'5',
                        ),
                    'is_colour' => Array(
                        'type'=>'boolean',
                        'default'=>1,
                        ),
                    'is_silent' => Array(
                        'type'=>'boolean',
                        'default'=>0,
                        ),
                    'date' => Array(
                        'type'=>'date',
                        'dc_element'=>'date',
                        'text_label'=>'Year',
                        'bibtex_element'=>'year'
                        ),
                    'date_released' => Array(
                        'type'=>'char',
                        'size'=>'4',
                        'dc_element'=>'date',
                        'text_label'=>'Date released'
                        ),
                    'date_production' => Array(
                        'type'=>'char',
                        'size'=>'4',
                        'dc_element'=>'date',
                        'text_label'=>'Year of production'
                        ),
                    'online_url' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'text_label'=>'Online URL',
                        ),
                    'online_price' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        'text_label'=>'Online price',
                        ),
                    'online_format' => Array(
                        'type'=>'many_to_one',
                        'select' => 'OnlineFormat.title AS online_format, online_format_id',
                        'join' => 'LEFT JOIN OnlineFormat ON online_format_id=OnlineFormat.id',
                        'text_label'=>'Online format',
                        ),
                    'online_format_id' => Array(
                        'type'=>'integer',
                        ),
                    'is_online' => Array(
                        'type'=>'boolean',
                        'default'=>0,
                        ),
                    'distributors_ref' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'isbn' => Array(
                        'type'=>'varchar',
                        'size'=>'100',
                        ),
                    'shelf_ref' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'ref' => Array(
                        'type'=>'varchar',
                        'size'=>'75',
                        ),
                    'physical_description' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'price' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'availability' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'viewfinder' => Array(
                        'type'=>'integer',
                        'default'=>0,
                        ),
                    'notes' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'notes_documentation' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'notes_uses' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'director' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'producer' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'format_summary' => Array(
                        'type'=>'integer',
                        'default'=>0,
                        ),
                    'distribution' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'keyword' => Array(
                        'type'=>'many_to_many',
                        'link'=>'TitleKeyword',
                        'select' => 'title',
                        'keys'=>Array('title_id', 'keyword_id'),
                        'lookup'=>'title',
                        'split'=>'; *',
                        'dc_element'=>'subject',
                        'text_label'=>'Keywords',
                        'bibtex_element'=>'keyword'
                        ),
                    'category' => Array(
                        'type'=>'many_to_many',
                        'link'=>'TitleCategory',
                        'select' => 'title,Category.id AS `key`',
                        'keys'=>Array('title_id', 'category_id'),
                        'lookup'=>'title',
                        'split'=>'; *',
                        'dc_element'=>'subject',
                        'text_label'=>'Subject',
                        'bibtex_element'=>'keyword'
                        ),
                    'title_format' => Array(
                        'type'=>'many_to_many',
                        'link'=>'TitleFormatLink',
                        'select' => 'title, TitleFormat.id AS `key`',
                        'keys'=>Array('title_id', 'format_id'),
                        'lookup'=>'title',
                        'dc_element'=>'format',
                        'text_label'=>'Format',
                        'bibtex_element'=>'howpublished'
                        ),
                    'country' => Array(
                        'type'=>'many_to_many',
                        'link'=>'TitleCountry',
                        'select' => 'title,Country.id AS `key`',
                        'keys'=>Array('title_id', 'country_id'),
                        'lookup'=>'title',
                        'text_label'=>'Country',
                        ),
                    'person' => Array(
                        'type'=>'many_to_many',
                        'link'=>'Participation',
                        'select' => 'Person.name, Person.name as title, Role.is_technical, Role.title AS role',
                        'join'=>'JOIN Participation ON Participation.person_id=Person.id
                                 JOIN Role on Participation.role_id=Role.id',
                        'keys'=>Array('title_id', 'person_id'),
                        'order'=>'Person.name',
                        'dc_element'=>'contributor',
                        'text_label'=>'Contributors',
                        'bibtex_element'=>'author'
                        ),
                    // Sections
                    'section' => Array(
                        'type'=>'one_to_many',
                        'foreign_key'=>'title_id',
                        'select' => 'title,description,notes,duration,is_colour,is_silent,distributors_ref,isbn,
                                    number_in_series',
                        ),
                    // Related titles
                    'related' => Array(
                        'type'=>'many_to_many',
                        'related_to' => 'title',
                        'link'=>'TitleRelation',
                        'select' => 'title',
                        'join'=>'JOIN TitleRelation ON TitleRelation.title2_id=Title.id',
                        'keys'=>Array('title1_id', 'title2_id'),
                        'order'=>'title',
                        ),
                    // Related organisations
                    'org' => Array(
                        'type'=>'many_to_many',
                        'link'=>'OrganisationParticipation',
                        'select' => 'name, OrganisationRelation.title AS relation,notes,contact_name,contact_position,email,web_url,telephone,fax,address_1,address_2,address_3,address_4,town,county,postcode,country',
                        'join'=>'JOIN OrganisationParticipation ON OrganisationParticipation.org_id=Organisation.id JOIN OrganisationRelation ON OrganisationRelation.id=OrganisationParticipation.org_relation_id',
                        'keys'=>Array('title_id', 'org_id'),
                        'order'=>'name',
                        ),
                    // Distribution media
                    'distribution_media' => Array(
                        'type'=>'one_to_many',
                        'foreign_key'=>'title_id',
                        'select' => 'type,format,price,availability,length,year',
                        ),
                    ),
                'dc_element_static' => Array(
                    'publisher' => 'British Universities Film & Video Council',
                    //'rights' => $this->copyright,
                    ),
                'bibtex_element_static' => Array(
                    'publisher' => 'British Universities Film & Video Council',
                    //'copyright' => $this->copyright,
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,t.description AS summary,t.date,t.director,t.producer,t.format,t.subject,t.alt_title,t.distribution"),
                    'index' => Array(
                        //### TODO: add notes fields
                        'default' => Array('type'=>'fulltext',
                            'fields'=>'t.title,t.title_series,t.description,t.misc,t.alt_title,t.subtitle'),
                        'title' => Array('type'=>'fulltext', 'fields'=>'t.title,t.title_series,t.alt_title,t.section_title'),
                        'description' => Array('type'=>'fulltext', 'fields'=>'t.description'),
                        'category' => Array('type'=>'number', 'fields'=>'TitleCategory.category_id',
                                'join'=>Array('JOIN TitleCategory ON TitleCategory.title_id=t.id')),
                        'person' => Array('type'=>'fulltext', 'fields'=>'Person.name',
                                'join'=>Array('JOIN Participation ON Participation.title_id=t.id',
                                    'JOIN Person ON Person.id=Participation.person_id')),
                        'keyword' => Array('type'=>'fulltext', 'fields'=>'Keyword.title',
                                'join'=>Array('JOIN TitleKeyword ON TitleKeyword.title_id=t.id',
                                    'JOIN Keyword ON Keyword.id=TitleKeyword.keyword_id')),
                        'title_format' => Array('type'=>'number', 'fields'=>'TitleFormatLink.format_id',
                                'join'=>Array('JOIN TitleFormatLink ON TitleFormatLink.title_id=t.id')),
                        'language' => Array('type'=>'string', 'fields'=>'t.language_id'),
                        'country' => Array('type'=>'string', 'fields'=>'TitleCountry.country_id',
                                'join'=>Array('JOIN TitleCountry ON TitleCountry.title_id=t.id')),
                        'org' => Array('type'=>'fulltext', 'fields'=>'Organisation.name',
                                'join'=>Array('JOIN OrganisationParticipation ON OrganisationParticipation.title_id=t.id',
                                    'JOIN Organisation ON Organisation.id=OrganisationParticipation.org_id')),
                        'shakespeare' => Array('type'=>'number', 'fields'=>'t.is_shakespeare'),
                        'online_url' => Array('type'=>'blank', 'fields'=>'t.online_url'),
                        'online_format' =>Array('type'=>'blank', 'fields'=>'t.online_format_id'),
                        'is_online' => Array('type'=>'number', 'fields'=>'t.is_online'),
                        'format_summary' => Array('type'=>'number', 'fields'=>'t.format_summary'),
                        'viewfinder' => Array('type'=>'number', 'fields'=>'t.viewfinder'),
                        'date' => Array('type'=>'datetime', 'fields'=>'t.date'),
                        'sort.title' => Array('type'=>'asc', 'fields'=>'t.title'),
                        'sort.date_asc' => Array('type'=>'asc', 'fields'=>'t.date'),
                        'sort.date_desc' => Array('type'=>'desc', 'fields'=>'t.date'),
                        'sort.relevance' => Array('type'=>'rel'),
                        'sort.default' => Array('type'=>'desc', 'fields'=>'t.date'),
                        ),
                    ),
                ),

            // Section
            'section' => Array(
                'title'=>'Section',
                'description'=>'Sub sections of a main title',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Section',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=>'hermes_id',
                        ),
                    'title_id' => Array(
                        'require'=>1,
                        'type'=>'integer',
                        ),
                    'title' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'description' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'notes' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'duration' => Array(
                        'type'=>'integer',
                        ),
                    'is_colour' => Array(
                        'type'=>'boolean',
                        ),
                    'is_silent' => Array(
                        'type'=>'boolean',
                        ),
                    'distributors_ref' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'isbn' => Array(
                        'type'=>'varchar',
                        'size'=>'100',
                        ),
                    'number_in_series' => Array(
                        'type'=>'integer',
                        ),
                    'keyword' => Array(
                        'type'=>'many_to_many',
                        'link'=>'SectionKeyword',
                        'select' => 'title',
                        'join'=>'JOIN SectionKeyword ON SectionKeyword.keyword_id=Keyword.id',
                        'keys'=>Array('section_id', 'keyword_id'),
                        'get'=>'col',
                        ),
                    'category' => Array(
                        'type'=>'many_to_many',
                        'link'=>'SectionCategory',
                        'select' => 'title',
                        'join'=>'JOIN SectionCategory ON SectionCategory.category_id=Category.id',
                        'keys'=>Array('section_id', 'category_id'),
                        'get'=>'col',
                        ),
                    'person' => Array(
                        'type'=>'many_to_many',
                        'link'=>'SectionParticipation',
                        'select' => 'Person.name, Person.name as title, Role.is_technical, Role.title AS role',
                        'join'=>'JOIN SectionParticipation ON SectionParticipation.person_id=Person.id
                                 JOIN Role on SectionParticipation.role_id=Role.id',
                        'keys'=>Array('section_id', 'person_id'),
                        'order'=>'Person.name',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,t.description AS summary,hermes_id AS `key`"),
                    ),
                ),

            // People
            'person' => Array(
                'title'=>'People',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Person',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'hermes_id',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'notes' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,hermes_id AS `key`"),
                    ),
                ),

            // Organisation
            'org' => Array(
                'title'=>'Organisation',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Organisation',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'hermes_id',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    /* ### TODO: needs another DS table added, leave for now
                    'type' => Array(
                        'type'=>'many_to_many',
                        'link'=>'OrganisationTypeLink',
                        'select' => 'title',
                        'join'=>'JOIN OrganisationTypeLink ON OrganisationTypeLink.org_type_id=OrganisationType.id',
                        'keys'=>Array('org_id', 'org_type_id'),
                        'get'=>'col',
                        ),
                     ### */
                    'notes' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'contact_name' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'contact_position' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'email' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'web_url' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'telephone' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'fax' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'address_1' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'address_2' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'address_3' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'address_4' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'town' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'county' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'postcode' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'country' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,hermes_id AS `key`"),
                    'index'=>Array('sort.default' => Array('type'=>'asc', 'fields'=>'t.title'),),
                    ),
                ),

            // Keywords
            'keyword' => Array(
                'title'=>'Keywords',
                'description'=>'Search enhancers. Non-controlled vocabulary.',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Keyword',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    ),
                ),

            // Category
            'category' => Array(
                'title'=>'Categories',
                'description'=>'Controlled vocabulary. Classification system.',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Category',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    'index'=>Array('sort.default' => Array('type'=>'asc', 'fields'=>'t.title'),),
                    ),
                ),

            // Organisation type
            'orgtype' => Array(
                'title'=>'Organisation Type',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'OrganisationType',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    ),
                ),

            // Country
            'country' => Array(
                'title'=>'Country',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Country',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    'index'=>Array('sort.default' => Array('type'=>'asc', 'fields'=>'t.title'),),
                    ),
                ),

            // Language
            'language' => Array(
                'title'=>'Language',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Language',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    'index'=>Array('sort.default' => Array('type'=>'asc', 'fields'=>'t.title'),),
                    ),
                ),

            // Character
            'character' => Array(
                'title'=>'Character',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'ShkCharacter',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'play' => Array(
                        'type'=>'many_to_one',
                        'select' => 'Play.title AS play',
                        'join' => 'JOIN Play ON play_id=Play.id',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.name AS title,'' AS summary,id AS `key`"),
                    ),
                ),

            // Format
            'title_format' => Array(
                'title'=>'Title format',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'TitleFormat',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.title,'' AS summary,id AS `key`"),
                    ),
                ),

            // Distribution media
            'distribution_media' => Array(
                'title'=>'Distribution Media',
                'description'=>'Distribution formats',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'DistributionMedia',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=> 'id',
                        ),
                    'title_id' => Array(
                        'require'=>1,
                        'type'=>'integer',
                        ),
                    'type' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'format' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'price' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'availability' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'length' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'year' => Array(
                        'type'=>'char',
                        'size'=>'4',
                        ),
                    ),
                'search'=>Array(
                    ),
                ),
            );
        DataSource::DataSource($config);
        }
    
    // Function to get proper title case
    // This utilizes the general title_case function with a few additions
    function title_case($title)
        {
        // words to capitalise
        $uc_stopwords = Array(
            '3m', '5s', 'tpm', 'diy', 'cbs', 'cbt', 'cdm',
            );
        // special words to handle (from shakespeare)
        $exact_stopwords = array(
            'macneil' => 'MacNeil', 'macmorris' => 'MacMorris',
            'macmillan' => 'MacMillan', 'machomer' => 'MacHomer',
            'macready' => 'MacReady', 'c\'est' => 'c\'est',
            );
        return title_case($title, null, $uc_stopwords, $exact_stopwords);
        }
    }

?>
