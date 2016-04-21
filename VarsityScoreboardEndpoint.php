<?php

class VarsityScoreboardEndpoint extends Endpoint {
    public function get_output($api_name_info) {
        $method = $_SERVER['REQUEST_METHOD'];
        $sheet = "https://docs.google.com/spreadsheets/d/1mbUHySFcc3oPAnnuGhkcOwIw-J5Iz19gSitp3G0d9ko/pub?output=csv";

        if ($method === 'GET') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sheet);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );

            $csv = curl_exec($ch);
            curl_close($ch);

            $reponse = array();
            $response['csv'] = $csv;
            return $response;
        }

        return array();
    }
}
