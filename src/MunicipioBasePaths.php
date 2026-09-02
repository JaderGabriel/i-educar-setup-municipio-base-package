<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase;

final class MunicipioBasePaths
{
    public static function repositoryRoot(): string
    {
        return dirname(__DIR__);
    }

    public static function seedersDataFile(string $filename): string
    {
        return self::repositoryRoot().'/database/seeders/data/'.ltrim($filename, '/');
    }
}
