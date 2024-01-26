<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Doctrine\Dbal;

use function Lambdish\Phunctional\filter;
use function Lambdish\Phunctional\map;
use function Lambdish\Phunctional\reduce;

final class DbalTypesSearcher
{
    private const MAPPINGS_PATH = 'Infrastructure/Persistence/Doctrine';

    public static function inPath(string $path, string $rootNamespace, ?string $contextName = null): array
    {
        $possibleDbalDirectories = self::possibleDbalPaths($path);
        $dbalDirectories = filter(self::isExistingDbalPath(), $possibleDbalDirectories);

        return reduce(self::dbalClassesSearcher($rootNamespace, $contextName), $dbalDirectories, []);
    }

    private static function modulesInPath(string $path): array
    {
        return filter(
            static fn (string $possibleModule): bool => !in_array($possibleModule, ['.', '..'], true),
            scandir($path)
        );
    }

    private static function possibleDbalPaths(string $path): array
    {
        return map(
            static function (mixed $_unused, string $module) use ($path) {
                $mappingsPath = self::MAPPINGS_PATH;

                return realpath("$path/$module/$mappingsPath");
            },
            array_flip(self::modulesInPath($path))
        );
    }

    private static function isExistingDbalPath(): callable
    {
        return static fn (string $path): bool => !empty($path);
    }

    private static function dbalClassesSearcher(string $rootNamespace, ?string $contextName = null): callable
    {
        return static function (array $totalNamespaces, string $path) use ($rootNamespace, $contextName): array {
            $possibleFiles = scandir($path);

            $files = filter(static fn (string $file): bool => self::endsWith('Type.php', $file), $possibleFiles);

            $namespaces = map(
                static function (string $file) use ($path, $rootNamespace, $contextName): string {
                    $fullPath = "$path/$file";
                    $splittedPath = $contextName ?
                        explode("/src/$contextName/", $fullPath) :
                        explode('/src/', $fullPath);

                    $classWithoutPrefix = str_replace(['.php', '/'], ['', '\\'], $splittedPath[1]);

                    if (!$contextName) {
                        return "$rootNamespace\\$classWithoutPrefix";
                    }

                    return "$rootNamespace\\$contextName\\$classWithoutPrefix";
                },
                $files
            );

            return array_merge($totalNamespaces, $namespaces);
        };
    }

    private static function endsWith(string $needle, string $haystack): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
