<?php

namespace Imarc\Millyard\Concerns;

use ReflectionClass;

trait DiscoversClasses
{
    private function discoverClassesForAttribute(string $attribute, string $pathSegment): array
    {
        $directory = sprintf('%s/app/%s', get_template_directory(), trim($pathSegment, '/'));
        $namespace = sprintf('\\App\\%s\\', $pathSegment);
        $classes = [];

        foreach (glob($directory . '/*.php') as $file) {
            $className = $namespace . basename($file, '.php');

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (!empty($reflection->getAttributes($attribute))) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
