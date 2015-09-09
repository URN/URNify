<?php

class CurrentSongEndpoint extends Endpoint {
    public function get_output($api_name_info) {
        $updateKey = 'MwaOOmqItO2rTNSH0^SypV%C3$5*J#FV!cfSSGLY2K@%Ube9%b#OIRy';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $reponse = array();
            $response['title'] = get_option('current_song_title');
            $response['artist'] = get_option('current_song_artist');
            $response['image_url'] = get_option('current_song_image_url');
            $response['large_image_url'] = get_option('current_song_large_image_url');
            $response['start_time'] = get_option('current_song_start_time');
            $response['length'] = get_option('current_song_length');
            return $response;
        }

        if ($method === 'POST') {
            $postedKey = urldecode($_POST['key']);

            if (isset($_POST['key']) && isset($_POST['update']) && $postedKey === $updateKey) {
                $song = json_decode(file_get_contents('http://urn1350.net/current-song.json'));
                update_option('current_song_title', $song->title);
                update_option('current_song_artist', $song->artist);
                update_option('current_song_image_url', $song->image);
                update_option('current_song_large_image_url', $song->image_large);
                update_option('current_song_start_time', $song->start_time);
                update_option('current_song_length', $song->length);

                return array("status" => "success", "message" => "Current song successfully updated");
            }
            else {
                return array("status" => "error", "message" => "Invalid update key");
            }
        }

        return array();
    }
}
