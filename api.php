<?php

class API {
    private static $endpoints = array();

    public static function addEndpoint($endpoint) {
        self::$endpoints[] = $endpoint;
    }

    public static function constructEndpoints() {
        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'api';
            return $query_vars;
        });

        foreach (self::$endpoints as $key => $endpoint) {
            add_action('init', function () use ($endpoint) {
                add_rewrite_rule('api' . $endpoint->getUrlRegex(), 'index.php?api=' . $endpoint->getName(), 'top');
            });

            add_filter('template_include', function ($template) use ($endpoint) {
                global $wp_query;

                if (isset( $wp_query->query_vars['api'] ) && !is_page() && !is_single()) {
                    $wp_query->is_404 = false;
                    $wp_query->is_archive = true;
                    $wp_query->is_category = true;

                    if ($wp_query->query_vars['api'] === $endpoint->getName()) {
                        header('Content-Type: application/json');
                        die(json_encode($endpoint->getOutput()));                
                    }
                } else {
                    return $template;
                }
            });
        }
    }
}
