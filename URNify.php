<?php
/*
Plugin Name: URNify
Plugin URI: https://github.com/URN/URNify
Description: This plugin pimps out WordPress to support radio-like features, including support for Shows that users can be added to and then make posts on. Also adds an option to the edit user page to give users committee membership that can be seen on their public user page. A basic JSON API is also implemented.
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

require 'ScheduleOverride.php';

require 'API.php';
require 'Endpoint.php';
require 'endpoints/ScheduleEndpoint.php';
require 'endpoints/ScheduleWeekEndpoint.php';
require 'endpoints/CurrentSongEndpoint.php';
require 'endpoints/SendMessageEndpoint.php';
require 'endpoints/VarsityMatchesEndpoint.php';
require 'endpoints/VarsityScoresEndpoint.php';

class URNify {
    public static function init() {
        new Committee();

        self::create_shows_taxonomy();
        self::create_api();
        self::hide_default_roles();
        self::create_schedule_override();

        // Rename the author base slug to 'member'
        // /author/<username> -> /member/<username>
        add_action('init', function () {
            global $wp_rewrite;
            $wp_rewrite->author_base = 'member';
        });
    }

    public static function hide_default_roles() {
        add_filter('editable_roles', function ($roles) {
            //Hide Defualt Roles
            if (isset($roles['author'])) {
                unset($roles['author']);
            }

            if (isset($roles['editor'])) {
                unset($roles['editor']);
            }

            if (isset($roles['subscriber'])) {
                unset($roles['subscriber']);
            }

            if (isset($roles['contributor'])) {
                unset($roles['contributor']);
            }

            return $roles;
        });
    }

    public static function create_shows_taxonomy() {
        $options = array();
        $options[] = new CustomOptionText('Name Prelude', 'name_prelude');
        $options[] = new CustomOptionText('Facebook Link', 'fb_link');
        $options[] = new CustomOptionText('Twitter Link', 'tw_link');
        $options[] = new CustomOptionText('Show Category', 'show_category');
        $options[] = new CustomOptionText('Image URL', 'image');
        $options[] = new CustomOptionCheckbox('Ended', 'ended', 'Has the show ended?');
        $options[] = new CustomOptionCheckbox('Hidden', 'hidden', 'Should the show be hidden?');
        $options[] = new CustomOptionTimeSlots('Time Slots', 'slot', 'When is this show on air?');

        $shows_taxonomy = new CustomTaxonomy('Show', 'Shows', 'dashicons-groups', 21, $options);
    }

    public static function create_api() {
        API::add_endpoint(new ScheduleWeekEndpoint('/schedule/week$', 'schedule_week'));
        API::add_endpoint(new CurrentSongEndpoint('/current_song$', 'current_song'));
        API::add_endpoint(new SendMessageEndpoint('/send_message$', 'send_message'));
        API::add_endpoint(new VarsityMatchesEndpoint('/varsity/matches$', 'varsity_matches'));
        API::add_endpoint(new VarsityScoresEndpoint('/varsity/scores$', 'varsity_scores'));
        API::construct_endpoints();
    }

    public static function create_schedule_override() {
        register_activation_hook( __FILE__, 'ScheduleOverride::create_database_table');
        ScheduleOverride::init();
    }
}

URNify::init();
