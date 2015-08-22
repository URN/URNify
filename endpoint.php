<?php

class Endpoint {
    private $urlRegex;
    private $name;
    public $output = array();

    public function __construct($urlRegex, $name) {
        $this->urlRegex = $urlRegex;
        $this->name = $name;
    }

    public function getUrlRegex() {
        return $this->urlRegex;
    }

    public function getName() {
        return $this->name;
    }

    public function getOutput() {
        return $this->output->__invoke();
    }
}
