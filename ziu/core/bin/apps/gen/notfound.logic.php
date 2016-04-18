<?php

class Notfound
{

    public function init()
    {
    }

    public function index()
    {
        $lib = lib('common');
        $lib->usage('Error: [target] not exists.');
    }

}

