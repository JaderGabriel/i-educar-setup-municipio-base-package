<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase\Database\Seeders;

use App\Models\LegacyAcademicYearStage;
use App\Models\LegacyEvaluationRule;
use App\Models\LegacyGeneralConfiguration;
use App\Models\LegacyInstitution;
use App\Models\LegacyRoundingTable;
use App\Models\LegacySchool;
use App\Models\LegacySchoolAcademicYear;
use App\Models\LegacyStageType;
use Database\Seeders\GerarTurmasSeeder;
use iEducar\Packages\BuritiSetup\Shared\MunicipalSetupSupport;
use iEducar\Packages\BuritiSetupMunicipioBase\MunicipioGenerico\MunicipioGenericoMunicipalData;
use iEducar\Packages\BuritiSetupMunicipioBase\MunicipioGenerico\MunicipioGenericoSchoolCsvSynchronizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Setup do perfil genérico: identidade, regra quantitativa, trimestres, pipeline municipal completo,
 * geração de turmas do ano vigente e preparação para auditoria/tipo_boletim (comando principal).
 */
class MunicipioGenericoSetupSeeder extends Seeder
{
    use MunicipalSetupSupport;

    private const USUARIO_CAD = 1;

    private const TIPO_NOTA_NUMERICA = 1;

    private const TIPO_PROGRESSAO_MEDIA_E_PRESENCA = 2;

    private const TIPO_PRESENCA_GERAL = 1;

    public function run(): void
    {
        $envId = env('IEDUCAR_SETUP_INSTITUICAO_ID');
        if ($envId !== null && $envId !== '') {
            $instituicoes = collect([(int) $envId]);
        } else {
            $instituicoes = LegacyInstitution::query()->pluck('cod_instituicao');
        }

        if ($instituicoes->isEmpty()) {
            $this->command?->warn('Nenhuma instituição encontrada. Cadastre a instituição antes do setup municipal base.');

            return;
        }

        $anoVigente = MunicipioGenericoMunicipalData::anoLetivoVigente();
        $instIds = $instituicoes->map(fn ($v) => (int) $v)->values()->all();

        foreach ($instituicoes as $instituicaoId) {
            $id = (int) $instituicaoId;
            $this->aplicarIdentidadeInstituicao($id);
            $this->aplicarRegraAvaliacaoQuantitativa($id);
            $this->garantirModuloTrimestre($id);
        }

        $instituicaoAlvoCsv = (int) $instituicoes->first();
        (new MunicipioGenericoSchoolCsvSynchronizer($this->command))->syncIfAvailable($instituicaoAlvoCsv);

        $this->runMunicipalStandardPostEscolasPipeline(
            withMunicipalAeeGeral: true,
            aplicarEducacaoInfantil: true,
            instituicaoIds: $instIds,
            anoLetivoEducacaoInfantil: $anoVigente,
        );

        $this->aplicarDatasTrimestresCalendarioMunicipal($instIds, $anoVigente);

        if (filter_var(env('IEDUCAR_MUNICIPIO_BASE_GERAR_TURMAS', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->info(sprintf('Gerando turmas regulares para o ano %d…', $anoVigente));
            $this->call(GerarTurmasSeeder::class, false, ['anos' => [$anoVigente]]);
        }

        $this->command?->info(sprintf(
            'Setup municipal genérico concluído (slug %s, ano vigente %d).',
            MunicipioGenericoMunicipalData::SLUG,
            $anoVigente
        ));
    }

    private function aplicarIdentidadeInstituicao(int $codInstituicao): void
    {
        $footer = sprintf(
            '%s — %s/%s | Prefeito(a): %s | Secretário(a) de Educação: %s (%s)',
            MunicipioGenericoMunicipalData::nomeInstituicao(),
            MunicipioGenericoMunicipalData::cidade(),
            MunicipioGenericoMunicipalData::uf(),
            MunicipioGenericoMunicipalData::nomePrefeito(),
            MunicipioGenericoMunicipalData::nomeSecretariaEducacao(),
            MunicipioGenericoMunicipalData::atoSecretaria()
        );

        LegacyInstitution::query()->where('cod_instituicao', $codInstituicao)->update([
            'nm_instituicao' => MunicipioGenericoMunicipalData::nomeInstituicao(),
            'orgao_regional' => MunicipioGenericoMunicipalData::orgaoRegional(),
            'cidade' => MunicipioGenericoMunicipalData::cidade(),
            'ref_sigla_uf' => MunicipioGenericoMunicipalData::uf(),
            'cep' => MunicipioGenericoMunicipalData::cep(),
            'nm_responsavel' => MunicipioGenericoMunicipalData::nomeSecretariaEducacao(),
        ]);

        LegacyGeneralConfiguration::query()->updateOrCreate(
            ['ref_cod_instituicao' => $codInstituicao],
            [
                'ieducar_entity_name' => MunicipioGenericoMunicipalData::nomeInstituicao(),
                'ieducar_internal_footer' => $footer,
            ]
        );
    }

    private function aplicarRegraAvaliacaoQuantitativa(int $instituicaoId): void
    {
        $regra = LegacyEvaluationRule::query()
            ->where('instituicao_id', $instituicaoId)
            ->orderBy('id')
            ->first();

        if ($regra === null) {
            $this->command?->warn("Instituição {$instituicaoId}: nenhuma regra de avaliação encontrada.");

            return;
        }

        $tabelaNumerica = LegacyRoundingTable::query()
            ->where('instituicao_id', $instituicaoId)
            ->where('tipo_nota', self::TIPO_NOTA_NUMERICA)
            ->orderBy('id')
            ->first();

        $tabelaNumericaId = $tabelaNumerica?->id ?? $regra->tabela_arredondamento_id;

        $formulaRecuperacaoId = $regra->formula_recuperacao_id
            ?? DB::table('modules.formula_media')
                ->where('instituicao_id', $instituicaoId)
                ->where('id', 2)
                ->value('id');

        $payload = [
            'nome' => MunicipioGenericoMunicipalData::nomeRegraAvaliacao(),
            'tipo_nota' => self::TIPO_NOTA_NUMERICA,
            'tipo_progressao' => self::TIPO_PROGRESSAO_MEDIA_E_PRESENCA,
            'tipo_presenca' => self::TIPO_PRESENCA_GERAL,
            'tabela_arredondamento_id' => $tabelaNumericaId,
            'tabela_arredondamento_id_conceitual' => null,
            'media' => MunicipioGenericoMunicipalData::mediaAprovacao(),
            'porcentagem_presenca' => MunicipioGenericoMunicipalData::frequenciaMinimaPercentual(),
            'parecer_descritivo' => 0,
            'media_recuperacao' => MunicipioGenericoMunicipalData::mediaAprovacao(),
            'tipo_recuperacao_paralela' => LegacyEvaluationRule::PARALLEL_REMEDIAL_PER_STAGE,
            'tipo_calculo_recuperacao_paralela' => LegacyEvaluationRule::PARALLEL_REMEDIAL_AVERAGE_SCORE,
            'media_recuperacao_paralela' => MunicipioGenericoMunicipalData::mediaAprovacao(),
            'nota_maxima_geral' => 10,
            'nota_maxima_exame_final' => 10,
            'nota_minima_geral' => 0,
            'qtd_casas_decimais' => 1,
        ];

        if ($formulaRecuperacaoId !== null) {
            $payload['formula_recuperacao_id'] = $formulaRecuperacaoId;
        }

        DB::table('modules.regra_avaliacao')
            ->where('id', $regra->id)
            ->where('instituicao_id', $instituicaoId)
            ->update($payload);
    }

    private function garantirModuloTrimestre(int $instituicaoId): void
    {
        LegacyStageType::query()->firstOrCreate(
            [
                'ref_cod_instituicao' => $instituicaoId,
                'nm_tipo' => 'Trimestre',
                'num_etapas' => 3,
            ],
            [
                'ref_usuario_cad' => self::USUARIO_CAD,
                'data_cadastro' => now(),
                'descricao' => 'Períodos avaliativos trimestrais (perfil genérico municipal).',
                'ativo' => 1,
            ]
        );

        LegacyStageType::query()
            ->where('ref_cod_instituicao', $instituicaoId)
            ->where('nm_tipo', 'Trimestre')
            ->where('num_etapas', 3)
            ->update(['ativo' => 1]);
    }

    /**
     * @param  array<int>  $codInstituicoes
     */
    private function aplicarDatasTrimestresCalendarioMunicipal(array $codInstituicoes, int $ano): void
    {
        $periodos = MunicipioGenericoMunicipalData::periodosModuloMunicipal($ano);

        foreach ($codInstituicoes as $instituicaoId) {
            $modulo = LegacyStageType::query()
                ->where('ref_cod_instituicao', $instituicaoId)
                ->where('nm_tipo', 'Trimestre')
                ->where('num_etapas', 3)
                ->where('ativo', 1)
                ->first();

            if ($modulo === null) {
                $this->command?->warn("Instituição {$instituicaoId}: módulo Trimestre não encontrado.");

                continue;
            }

            $escolas = LegacySchool::query()
                ->where('ref_cod_instituicao', $instituicaoId)
                ->where('ativo', 1)
                ->get(['cod_escola']);

            foreach ($escolas as $escola) {
                LegacySchoolAcademicYear::query()->firstOrCreate(
                    [
                        'ref_cod_escola' => $escola->cod_escola,
                        'ano' => $ano,
                    ],
                    [
                        'ref_usuario_cad' => self::USUARIO_CAD,
                        'andamento' => LegacySchoolAcademicYear::IN_PROGRESS,
                        'ativo' => 1,
                    ]
                );

                $ealPk = $this->escolaAnoLetivoPk((int) $escola->cod_escola, $ano);
                if ($ealPk === null) {
                    continue;
                }

                foreach ($periodos as $p) {
                    LegacyAcademicYearStage::query()->updateOrCreate(
                        [
                            'ref_ano' => $ano,
                            'ref_ref_cod_escola' => $escola->cod_escola,
                            'sequencial' => $p['sequencial'],
                            'ref_cod_modulo' => $modulo->cod_modulo,
                        ],
                        [
                            'data_inicio' => $p['data_inicio'],
                            'data_fim' => $p['data_fim'],
                            'dias_letivos' => $p['dias_letivos'],
                            'escola_ano_letivo_id' => $ealPk,
                        ]
                    );
                }
            }
        }

        $this->command?->info("Datas dos trimestres (ano {$ano}) aplicadas em ano_letivo_modulo.");
    }
}
