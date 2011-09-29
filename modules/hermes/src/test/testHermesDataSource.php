<?php
// $Id$
// Tests for Hermes DataSource
// Phil Hansen, 03 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');
require_once($CONF['path_src'] . 'datasource/test/BaseDataSourceTestCase.class.php');
    
// Some static test data
$HERMES_TEST_ITEM = Array(
        'title'=>'Test Title',
        'description'=>'Test summary/description/abstract',
        'notes'=>"Notes\nBU Notes",
        'date' => '1979',
        'tx_date' => '1979-03-02',
        'duration' => '6805',
        'category' => Array('Category1', 'Category 2'),
        'keyword' => Array("Key'word1", 'Keyword 2'),
        'person' => Array('Person 1', 'Person2', 'Person 3'),
        'media' => Array(Array('title'=>'Test Title MP3', 'location'=>'B001/file.mp3', 'content_type'=>'audio/mpeg')),
        'tape_number' => '141',
        'tape_barcode' => 'BU-B001-00012',
        'mets_id' => 'IRN_1979_00141_01_BU-B001-00012_DMD_001',
        'hidden' => '0',
        'language' => 'English',
        );

class HermesDataSourceTestCase
    extends BaseDataSourceTestCase
    {
    function new_datasource()
        {
        global $MODULE;
        return $MODULE->new_datasource();
        }

    var $test_data = Array(
        // General data
        'url' => '/title/test-title',
        'title' => 'Test title record',
        'subtitle' => 'Sub title',
        'alt_title' => 'Alternate title',
        'title_series' => 'Series title',
        'description' => 'Description',

        // Technical information
        'language' => 'Afrikaans',
        'language_id' => 'af',
        'is_colour' => 1,
        'is_silent' => 1,
        'date' => '2007-06-05',
        'date_released' => '2001',
        'date_production' => '2002',

        // Additional details
        'distributors_ref' => 'abcde',
        'isbn' => '123456',
        'shelf_ref' => '789',
        'ref' => '10',
        'physical_description' => 'physical_description',
        'price' => 'price',
        'availability' => 'availability',
        'online_url' => 'online url',
        'online_price' => 'free',
        'online_format' => 'Download',
        'online_format_id' => 2,
        'is_online' => 1,
        'format_summary' => 3,

        // Notes
        'notes' => 'notes',
        'notes_documentation' => 'notes_documentation',
        'notes_uses' => 'notes_uses',

        // Arrays
        'keyword' => Array('acting', 'acting techniques'),
        'category' => Array(
            Array('key'=>'1', 'title'=>'Dance'),
            Array('key'=>'2', 'title'=>'Drama'),
            ),
        'title_format' => Array(
            Array('url'=>'/title_format/1','title'=>'CD-i', 'key'=>'1'),
            Array('url'=>'/title_format/2','title'=>'CD-ROM', 'key'=>'2'),
            ),
        'country' => Array(
            Array('url'=>'/country/ad','title'=>'Andorra, Principality of', 'id'=>'ad'),
            Array('url'=>'/country/ae','title'=>'United Arab Emirates', 'id'=>'ae'),
            ),
        'org' => Array(
            //### sort order
            Array(
                'url'=>'/org/2',
                'name'=>'Another Test Org',
                'relation' => 'Archive',
                'notes' => '',
                'contact_name' => '',
                'contact_position' => '',
                'email' => '',
                'web_url' => '',
                'telephone' => '',
                'fax' => '',
                'address_1' => '',
                'address_2' => '',
                'address_3' => '',
                'address_4' => '',
                'town' => '',
                'county' => '',
                'postcode' => '',
                'country' => '',
                ),
            Array(
                'url'=>'/org/test-org',
                'name'=>'Test Organisation',
                'relation' => 'Related',
                'notes' => 'Some notes',
                'contact_name' => 'Fred Smith',
                'contact_position' => 'Position',
                'email' => 'fred@example.com',
                'web_url' => 'http://example.org',
                'telephone' => '123456789',
                'fax' => '987654321',
                'address_1' => 'address_1',
                'address_2' => 'address_2',
                'address_3' => 'address_3',
                'address_4' => 'address_4',
                'town' => 'town',
                'county' => 'county',
                'postcode' => 'postcode',
                'country' => 'country',
                ),
            ),
        'person' => Array(
            // Non-technical role without play character
            Array(
                'url'=>'/person/2',
                'name'=>'Another Test',
                'is_technical'=>0,
                'role'=>'Missing Character',
                ),
            // Technical role
            Array(
                'url'=>'/person/test-person',
                'name'=>'Test Person',
                'is_technical'=>1,
                'role'=>'Adaptor for Radio',
                ),
            ),
        'section' => Array(
            Array(
                'url'=>'/section/2',
                'title_id'=>'1',
                'title'=>'Another test section title',
                'description'=>'',
                'notes'=>'',
                'duration'=>0,
                'is_colour'=>0,
                'is_silent'=>0,
                'distributors_ref' => '',
                'isbn' => '',
                'number_in_series'=>'2',
                ),
            Array(
                'url'=>'/section/test-section',
                'title_id'=>'1',
                'title'=>'Test section title',
                'description'=>'section description',
                'notes'=>'section notes',
                'duration'=>1800,
                'is_colour'=>1,
                'is_silent'=>1,
                'distributors_ref' => 'abcde',
                'isbn' => '123456',
                'number_in_series'=>'1',
                'keyword' => Array('acting'),
                'category' => Array('Dance', 'Drama'),
                ),
            ),
        'distribution_media' => Array(
            Array(
                'url'=>'/distribution_media/1',
                'title_id'=>'1',
                'type'=>'DVD',
                'format'=>'PAL',
                'price'=>'$19.99',
                'availability'=>'Sale',
                'length'=>'120 mins',
                'year'=>'2008',
                ),
            Array(
                'url'=>'/distribution_media/2',
                'title_id'=>'1',
                'type'=>'Audio',
                'format'=>'CD',
                'price'=>'$12.99',
                'availability'=>'Sale',
                'length'=>'50 mins',
                'year'=>'2008',
                ),
            ),
        );

    function setup()
        {
        parent::setup();
        }

    function test_search()
        {
        // All searches which should return one result
        $tests = Array(
                // Full text indexes
                "{default=Test}",
                "{title=Test}",
                // "{series=Series}",
                "{description=Description}",
                "{person=\"Test Person\"}",
                "{keyword=\"acting techniques\"}",

                // Filters
                // Note these match on key field not string value
                "{title_format=1}",
                "{category=1}",
                "{language=af}",
                "{country=ae}",
                "{org=\"Test Organisation\"}",
                "{shakespeare=0}",
                "{is_online=1}",
                "{format_summary&2}",

                // Date
                "{date=1978-01-01,2008-12-31}",
                // "{date=1979-01-01,1986-12-31}", ///### FIXME - this fails for some reason
                
                // Sort
                "{sort=title}{default=Test}",
                "{sort=date_asc}{default=Test}",
                "{sort=date_desc}{default=Test}",
                );

        // Create with data
        $record = $this->ds->create('/title', $this->test_data);
        foreach ($tests as $test)
            {
            // println("### $test");
            $r = $this->ds->search('/title', $test, 0, 10);
            $this->assertNoError();
            
            $this->assertResults($r, 0, 1, 1);
            }
        $this->ds->delete($record['url']);

        // Create with no data then update
        $record = $this->ds->create('/title', Array('title'=>'foo'));
        $this->ds->update($record['url'], $this->test_data);
        foreach ($tests as $test)
            {
            // print "### $test\n";
            $r = $this->ds->search('/title', $test, 0, 10);
            $this->assertNoError();
            $this->assertResults($r, 0, 1, 1);
            }
        $this->ds->delete($record['url']);
        }

    function test_title_case()
        {
        // these represent title case tests specific to hermes
        $tests = Array(
            '' => '',
            '5s: five steps to shaping up the shopfloor' => '5S: Five Steps to Shaping Up the Shopfloor',
            '5s for safety' => '5S for Safety',
            '12 steps to implementing tpm, the' => '12 Steps to Implementing TPM, The',
            '"how to" diy guide to advanced plumbing' => '"How to" DIY Guide to Advanced Plumbing',
            'cbs, cbt, cdm, 3m' => 'CBS, CBT, CDM, 3M',
            );

        foreach ($tests as $test=>$expected)
            $this->assertEqual($this->ds->title_case($test), $expected);
        }
    }
