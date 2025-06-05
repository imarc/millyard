<?php

namespace Imarc\Millyard\Http;

use Timber\Timber;

class Controller
{
    protected function render($template, $data = [])
    {
        Timber::render($template, $data);
        exit;
    }
}
