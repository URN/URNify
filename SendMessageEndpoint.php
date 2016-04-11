<?php

class SendMessageEndpoint extends Endpoint {
    public function get_output($api_name_info) {
        if (isset($_POST['message'])) {
            $sender_ip = $_SERVER['REMOTE_ADDR'];

            $type = 'web';

            if (strpos($_SERVER['HTTP_USER_AGENT'], 'URN Android') !== false) {
                $type = 'android';
            }

            $url = 'http://int.urn1350.net:8080/web/submit_message.php?type=' . $type . '&message=' . urlencode($_POST['message']);
            $url .= '&sender=' . urlencode($sender_ip);
            $data = file_get_contents($url);

            if ($data === 'OK') {
                return array("status" => "success", "message" => "Message sent successfully");
            }
            else {
                return array("status" => "error", "message" => "Message couldn't be sent");
            }
        }
        else {
            return array("status" => "error", "message" => "No message sent");
        }
    }
}
