<?php

class Endpoint {
    private $urlRegex;
    private $name;

    public function __construct($urlRegex, $name) {
        $this->urlRegex = $urlRegex;
        $this->name = $name;
    }

    public function get_url_regex() {
        return $this->urlRegex;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_output($match) {
        return array();
    }
}
