<?php

class API {
    private static $endpoints = array();

    public static function add_endpoint($endpoint) {
        self::$endpoints[] = $endpoint;
    }

    public static function construct_endpoints() {
        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'api';
            return $query_vars;
        });

        foreach (self::$endpoints as $key => $endpoint) {
            add_action('init', function () use ($endpoint) {
                add_rewrite_rule('api' . $endpoint->get_url_regex(), 'index.php?api=' . $endpoint->get_name(), 'top');
            });

            add_filter('template_include', function ($template) use ($endpoint) {
                global $wp_query;

                if (isset( $wp_query->query_vars['api'] ) && !is_page() && !is_single()) {
                    $wp_query->is_404 = false;
                    $wp_query->is_archive = true;
                    $wp_query->is_category = true;

                    $base_name = str_replace('$matches[1]', '', $endpoint->get_name());
                    if (strpos($wp_query->query_vars['api'], $base_name) !== false) {
                        $match = str_replace($base_name, '', $wp_query->query_vars['api']);
                        header('Content-Type: application/json');
                        header('Access-Control-Request-Headers: X-Requested-With, accept, content-type');
                        header('Access-Control-Allow-Methods: GET, POST');
                        die(json_encode($endpoint->get_output($match)));
                    }
                } else {
                    return $template;
                }
            });
        }
    }
}
