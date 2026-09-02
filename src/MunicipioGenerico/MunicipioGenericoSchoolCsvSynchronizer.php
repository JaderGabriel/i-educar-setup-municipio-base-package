<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase\MunicipioGenerico;

use iEducar\Packages\BuritiSetup\Shared\AbstractInepMunicipalSchoolCsvSynchronizer;
use iEducar\Packages\BuritiSetupMunicipioBase\MunicipioBasePaths;

final class MunicipioGenericoSchoolCsvSynchronizer extends AbstractInepMunicipalSchoolCsvSynchronizer
{
    protected function csvBasename(): string
    {
        return MunicipioGenericoMunicipalData::csvBasename();
    }

    protected function inepFonteTag(): string
    {
        return 'municipio-generico-br-setup';
    }

    protected function ibgeMunicipio(): string
    {
        return MunicipioGenericoMunicipalData::ibgeMunicipio();
    }

    protected function defaultCepDigits(): string
    {
        return (string) MunicipioGenericoMunicipalData::cep();
    }

    protected function csvNotFoundMessage(): string
    {
        $basename = MunicipioGenericoMunicipalData::csvBasename();

        return "CSV de escolas não encontrado ({$basename}). Coloque o arquivo em "
            .MunicipioBasePaths::seedersDataFile($basename)
            .' ou defina IEDUCAR_MUNICIPIO_BASE_CSV. O setup continua sem importar escolas.';
    }

    public function syncIfAvailable(int $instituicaoId): int
    {
        try {
            return $this->sync($instituicaoId);
        } catch (\RuntimeException $e) {
            $this->artisan?->warn($e->getMessage());

            return 0;
        }
    }

    public function resolveCsvPath(): string
    {
        $basename = MunicipioGenericoMunicipalData::csvBasename();
        $candidates = [
            MunicipioBasePaths::seedersDataFile($basename),
            database_path('seeders/Setup/data/'.$basename),
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        throw new \RuntimeException($this->csvNotFoundMessage());
    }
}
