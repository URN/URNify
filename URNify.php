<?php
/*
Plugin Name: URNify
Plugin URI: https://github.com/URN/URNify
Description: This plugin pimps out WordPress to support radio-like features, including support for Shows and Podcasts that users can be added to and then make posts on. Also adds an option to the edit user page to give users committee membership that can be seen on their public user page. A basic JSON API is also implemented.
Version: 0.2
Author: James Turner
Author URI: https://github.com/jamesturner
*/

// https://codex.wordpress.org/Writing_a_Plugin#Plugin_Files
defined('ABSPATH') or die('No script kiddies please!');

if (!defined('WPINC')) {
    die;
}

require 'CustomTaxonomy.php';

require 'CustomOptionText.php';
require 'CustomOptionCheckbox.php';
require 'CustomOptionTimeSlots.php';

require 'Committee.php';

require 'API.php';
require 'Endpoint.php';
require 'ScheduleWeekEndpoint.php';
require 'ScheduleDayEndpoint.php';

class URNify {
    public static function init() {
        new Committee();

        self::create_shows_taxonomy();
        self::create_podcasts_taxonomy();
        self::create_api();
    }

    public static function create_shows_taxonomy() {
        $options = array();
        $options[] = new CustomOptionText('Name Prelude', 'name_prelude');
        $options[] = new CustomOptionText('Facebook Link', 'fb_link');
        $options[] = new CustomOptionText('Twitter Link', 'tw_link');
        $options[] = new CustomOptionText('Show Category', 'show_category');
        $options[] = new CustomOptionCheckbox('Ended', 'ended', 'Has the show ended?');
        $options[] = new CustomOptionCheckbox('Hidden', 'hidden', 'Should the show be hidden?');
        $options[] = new CustomOptionTimeSlots('Time Slots', 'slot', 'When is this show on air?');

        $shows_taxonomy = new CustomTaxonomy('Show', 'Shows', 'dashicons-groups', 21, $options);
    }

    public static function create_podcasts_taxonomy() {
        $options = array();
        $options[] = new CustomOptionText('Facebook Link', 'fb_link');
        $options[] = new CustomOptionText('Twitter Link', 'tw_link');
        $options[] = new CustomOptionText('iTunes Link', 'itunes_link');
        $options[] = new CustomOptionCheckbox('Ended', 'ended', 'Has the podcast ended?');
        $options[] = new CustomOptionCheckbox('Hidden', 'hidden', 'Should the podcast be hidden?');

        $podcasts_taxonomy = new CustomTaxonomy('Podcast', 'Podcasts', 'dashicons-microphone', 22, $options);
    }

    public static function create_api() {
        API::add_endpoint(new ScheduleWeekEndpoint('/schedule/week$', 'schedule_week'));
        API::add_endpoint(new ScheduleDayEndpoint('/schedule/day/(monday|tuesday|wednesday|thursday|friday)$', 'schedule_$matches[1]'));
        API::construct_endpoints();
    }
}

URNify::init();
