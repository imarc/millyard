<?php

namespace Imarc\Millyard\AdminPages;

use Imarc\Millyard\Attributes\RegistersAdminPage;
use Imarc\Millyard\Concerns\DiscoversClasses;

class Registrar
{
    use DiscoversClasses;

    public function registerAdminPages(string $path = 'AdminPages'): void
    {
        $adminPageClasses = $this->discoverClassesForAttribute(RegistersAdminPage::class, $path);

        foreach ($adminPageClasses as $adminPageClass) {
            $this->registerAdminPage($adminPageClass);
        }
    }

    public function registerAdminPage(string $adminPageClass): void
    {
        $adminPage = new $adminPageClass();

        if (! method_exists($adminPage, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $adminPageClass));
        }

        $adminPage->register();
        do_action('millyard_adminPage_registered', $adminPageClass);
    }
}