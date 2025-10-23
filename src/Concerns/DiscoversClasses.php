<?php

namespace Imarc\Millyard\Concerns;

use ReflectionClass;

trait DiscoversClasses
{
    private function discoverClassesForAttribute(string $attribute, string $pathSegment): array
    {
        $directory = sprintf('%s/app/%s', get_template_directory(), trim($pathSegment, '/'));
        $namespace = sprintf('App\\%s\\', $pathSegment);
        $classes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($directory . '/', '', $file->getPathname());
            $relativePath = str_replace('/', '\\', $relativePath);
            $className = $namespace . str_replace('.php', '', $relativePath);

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (! empty($reflection->getAttributes($attribute))) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
