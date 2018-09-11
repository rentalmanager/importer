<?php
/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 5:43 AM
 */

return [


    /*
     * Importer config
     * --------------------------------------------------------------------
     * Config for the main importer package
     * --------------------------------------------------------------------
     */

    /*
     * LOG channels
     */
    'log' => 'importer',


    /*
     * How to store the feed file name as..
     */

    'feed_file_name' => 'latest',

    /*
     * Feed files for the parsers
     * - By provider - it can be an array - multiple feed files provided
     */

    'feeds' => [
        'rentbits' => [
            [
                'from' => 'http',
                'location' => '', // set a url
                'external_feed_extension' => 'xml',
                'store_as_extension' => 'xml',
                'do_after_download' => false,
                'parent_element' => 'property',
                'parser' => 'xml'
            ]
        ],
        'apartmentlist' => [
            [
                'from' => 'http',
                'location' => '', // set a url
                'external_feed_extension' => 'json.gz',
                'store_as_extension' => 'json',
                'do_after_download' => 'gunzip',
                'parent_element' => 'properties',
                'parser' => 'json'
            ]
        ],
        'rentlingo' => [
            [
                'from' => 'http',
                'location' => '', // set a url
                'external_feed_extension' => 'xml',
                'store_as_extension' => 'xml',
                'do_after_download' => false,
                'parent_element' => 'ad',
                'parser' => 'xml'
            ]
        ],
        'zumper' => [
            [
                'from' => 'http',
                'location' => '', // set a url
                'external_feed_extension' => 'xml.gz',
                'store_as_extension' => 'xml',
                'do_after_download' => 'gunzip',
                'parent_element' => 'community',
                'parser' => 'xml'
            ]
        ]
    ]



];
