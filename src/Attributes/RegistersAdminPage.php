<?php

namespace Imarc\Millyard\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RegistersAdminPage
{
    public function __construct()
    {
    }
}
