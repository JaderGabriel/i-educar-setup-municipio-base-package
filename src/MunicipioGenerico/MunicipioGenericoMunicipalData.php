<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase\MunicipioGenerico;

/**
 * Dados padrão do perfil genérico. Sobrescreva via `.env` antes do setup ou copie esta classe
 * ao criar um município real (ver docs/CRIAR-NOVA-CIDADE.md).
 */
final class MunicipioGenericoMunicipalData
{
    public const SLUG = 'municipio-generico-br';

    public static function nomeInstituicao(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_NOME_INSTITUICAO', 'Secretaria Municipal de Educação — Município Genérico');
    }

    public static function orgaoRegional(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_ORGAO_REGIONAL', 'SME');
    }

    public static function cidade(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_CIDADE', 'Município Genérico');
    }

    public static function uf(): string
    {
        return strtoupper((string) env('IEDUCAR_MUNICIPIO_BASE_UF', 'BR'));
    }

    public static function cep(): int
    {
        $cep = preg_replace('/\D/', '', (string) env('IEDUCAR_MUNICIPIO_BASE_CEP', '00000000'));

        return (int) ($cep !== '' ? $cep : '00000000');
    }

    public static function ibgeMunicipio(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_IBGE', '0000000');
    }

    public static function nomePrefeito(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_PREFEITO', 'Prefeito(a) Municipal');
    }

    public static function nomeSecretariaEducacao(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_SECRETARIO_EDUCACAO', 'Secretário(a) de Educação');
    }

    public static function atoSecretaria(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_ATO_SECRETARIA', 'Portaria SME nº 001/'.date('Y'));
    }

    public static function mediaAprovacao(): float
    {
        return (float) env('IEDUCAR_MUNICIPIO_BASE_MEDIA_APROVACAO', 5.0);
    }

    public static function frequenciaMinimaPercentual(): float
    {
        return (float) env('IEDUCAR_MUNICIPIO_BASE_FREQUENCIA_MINIMA', 75.0);
    }

    public static function nomeRegraAvaliacao(): string
    {
        $nome = (string) env(
            'IEDUCAR_MUNICIPIO_BASE_NOME_REGRA_AVALIACAO',
            'Rede municipal genérica (média 5,0, 3 trimestres)'
        );

        return mb_substr($nome, 0, 50);
    }

    public static function anoLetivoVigente(): int
    {
        $v = env('IEDUCAR_MUNICIPIO_BASE_ANO_LETIVO');

        return $v !== null && $v !== '' ? (int) $v : (int) date('Y');
    }

    public static function csvBasename(): string
    {
        return (string) env('IEDUCAR_MUNICIPIO_BASE_CSV', 'municipio-generico-escolas.csv');
    }

    /**
     * Trimestres letivos padrão (ajuste conforme calendário oficial do município).
     *
     * @return list<array{sequencial: int, data_inicio: string, data_fim: string, dias_letivos: int}>
     */
    public static function periodosModuloMunicipal(int $ano): array
    {
        return [
            [
                'sequencial' => 1,
                'data_inicio' => sprintf('%d-02-03', $ano),
                'data_fim' => sprintf('%d-04-30', $ano),
                'dias_letivos' => 65,
            ],
            [
                'sequencial' => 2,
                'data_inicio' => sprintf('%d-05-05', $ano),
                'data_fim' => sprintf('%d-08-15', $ano),
                'dias_letivos' => 65,
            ],
            [
                'sequencial' => 3,
                'data_inicio' => sprintf('%d-08-18', $ano),
                'data_fim' => sprintf('%d-12-15', $ano),
                'dias_letivos' => 70,
            ],
        ];
    }
}
