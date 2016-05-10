<?php

class CurrentSongEndpoint extends Endpoint {
    private static function checkMultipleIssetPost($parameters) {
        $valid = true;
        foreach ($parameters as $parameter) {
            if (!isset($_POST[$parameter])) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function get_output($api_name_info) {
        $updateKey = constant('API_UPDATE_KEY');
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $reponse = array();
            $response['title'] = get_option('current_song_title');
            $response['artist'] = get_option('current_song_artist');
            $response['image_url'] = get_option('current_song_image_url');
            $response['large_image_url'] = get_option('current_song_large_image_url');
            $response['start_time'] = get_option('current_song_start_time');
            $response['duration'] = get_option('current_song_duration');
            return $response;
        }

        if ($method === 'POST') {
            $postedKey = urldecode($_POST['key']);
            if (isset($_POST['key']) && $postedKey === $updateKey) {
                if (self::checkMultipleIssetPost(array('title', 'artist', 'start_time', 'length', 'image', 'image_large'))) {
                    update_option('current_song_title', urldecode(stripslashes($_POST['title'])));
                    update_option('current_song_artist', urldecode(stripslashes($_POST['artist'])));
                    update_option('current_song_image_url', urldecode(stripslashes($_POST['image'])));
                    update_option('current_song_large_image_url', urldecode(stripslashes($_POST['image_large'])));
                    update_option('current_song_start_time', urldecode(stripslashes($_POST['start_time'])));
                    update_option('current_song_duration', urldecode(stripslashes($_POST['length'])));

                    return array("status" => "success", "message" => "Current song successfully updated");
                }
            }
            else {
                return array("status" => "error", "message" => "Invalid update key");
            }
        }

        return array();
    }
}
