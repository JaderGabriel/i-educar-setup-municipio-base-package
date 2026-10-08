<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase\Console\Commands;

use App\Models\LegacyCourse;
use App\Models\LegacyGrade;
use App\Models\LegacySchool;
use App\Models\LegacySchoolClass;
use Database\Seeders\SeederMaster;
use iEducar\Packages\BuritiSetup\Shared\EducacaoInfantilInstituicaoResolver;
use iEducar\Packages\BuritiSetup\Shared\EducacaoInfantilSetupPadrao;
use iEducar\Packages\BuritiSetup\Shared\IeducarSetupContext;
use iEducar\Packages\BuritiSetup\Shared\SetupCadastroComumService;
use iEducar\Packages\BuritiSetup\Shared\SetupOnWrongDatabase;
use iEducar\Packages\BuritiSetup\Shared\SetupPosExecucaoService;
use iEducar\Packages\BuritiSetup\Shared\SetupTenantGuard;
use iEducar\Packages\BuritiSetupMunicipioBase\Database\Seeders\MunicipioGenericoSetupSeeder;
use iEducar\Packages\BuritiSetupMunicipioBase\MunicipioGenerico\MunicipioGenericoMunicipalData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Setup municipal genérico: fluxo completo sem perfis de cidades reais.
 *
 * @see docs/USO.md
 */
final class IeducarSetupMunicipioBase extends Command
{
    protected $signature = 'ieducar:setup-municipio-base
                            {--skip-master : Não executa o SeederMaster}
                            {--infantil= : Padrão infantil (bercario-g1-g5 ou bncc-4-etapas)}
                            {--skip-auditoria : Não executa auditoria de cursos ao final}
                            {--skip-tipo-boletim : Não define tipo_boletim nas turmas}
                            {--auditoria-sem-corrigir : Auditoria sem correção automática}
                            {--sem-turmas : Não gera turmas regulares (somente AEE)}
                            {--dry-run : Simula auditoria e tipo_boletim}
                            {--ano= : Ano letivo (padrão: IEDUCAR_MUNICIPIO_BASE_ANO_LETIVO ou ano corrente)}
                            {--database= : Conexão do município. No multi-tenant precisa ser a da cidade configurada}
                            {--force-production : Permite em APP_ENV=production}';

    protected $description = 'Setup municipal genérico: BNCC, calendário, perfis, turmas, auditoria e tipos de boletim';

    public function handle(): int
    {
        if (App::environment('production') && !(bool) $this->option('force-production')) {
            $this->error('Comando recusado em APP_ENV=production. Use homologação ou --force-production.');

            return self::FAILURE;
        }

        if ((bool) $this->option('sem-turmas')) {
            putenv('IEDUCAR_MUNICIPIO_BASE_GERAR_TURMAS=false');
        }

        try {
            $padraoInfantil = EducacaoInfantilSetupPadrao::resolve(
                $this->option('infantil') ?: env('IEDUCAR_SETUP_INFANTIL_PADRAO')
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        IeducarSetupContext::setEducacaoInfantilPadrao($padraoInfantil);
        IeducarSetupContext::setEnsinoMedioApenasCursoNoFluxoPadrao(true);

        $slug = SetupTenantGuard::slugFromCity(
            MunicipioGenericoMunicipalData::cidade(),
            MunicipioGenericoMunicipalData::uf(),
        );

        if ($slug === '') {
            $slug = SetupTenantGuard::SLUG_GENERICO_SEM_CIDADE;
        }

        try {
            $connection = SetupTenantGuard::pin($slug);
        } catch (SetupOnWrongDatabase $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $ano = $this->resolveAnoLetivo();

        $this->info('🚀 Setup municipal genérico (perfil '.$slug.')');
        $this->line('👶 Educação Infantil: '.EducacaoInfantilSetupPadrao::label($padraoInfantil));
        $this->line('📅 Ano letivo alvo: '.$ano);
        Log::channel('daily')->info('ieducar:setup-municipio-base iniciado', ['ano' => $ano]);

        $startTime = microtime(true);
        $skipMaster = (bool) $this->option('skip-master');
        $steps = ($skipMaster ? 0 : 1) + 1;
        $bar = $this->output->createProgressBar($steps);
        $bar->start();

        try {
            if (!$skipMaster) {
                $this->call('db:seed', SetupTenantGuard::seed(SeederMaster::class, $connection));
                (new SetupCadastroComumService($this))->aplicar(
                    instituicaoIds: EducacaoInfantilInstituicaoResolver::ids(),
                    anoLetivo: $ano,
                );
                $bar->advance();
            }

            $this->call('db:seed', SetupTenantGuard::seed(MunicipioGenericoSetupSeeder::class, $connection));
            $bar->advance();
        } catch (\Throwable $e) {
            $this->error('❌ Erro no setup: '.$e->getMessage());
            Log::channel('daily')->error('ieducar:setup-municipio-base falhou', ['exception' => $e]);

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();

        $this->exibirRelatorio(microtime(true) - $startTime);

        (new SetupPosExecucaoService($this))->aplicar(
            instituicaoIds: EducacaoInfantilInstituicaoResolver::ids(),
            anoLetivo: $ano,
            executarAuditoria: !(bool) $this->option('skip-auditoria'),
            corrigirAuditoria: !(bool) $this->option('auditoria-sem-corrigir'),
            executarTipoBoletim: !(bool) $this->option('skip-tipo-boletim'),
            dryRun: (bool) $this->option('dry-run'),
        );

        return self::SUCCESS;
    }

    private function resolveAnoLetivo(): int
    {
        if ($this->option('ano') !== null && $this->option('ano') !== '') {
            return (int) $this->option('ano');
        }

        return MunicipioGenericoMunicipalData::anoLetivoVigente();
    }

    private function exibirRelatorio(float $executionTime): void
    {
        $schools = LegacySchool::count();
        $courses = LegacyCourse::count();
        $grades = LegacyGrade::count();
        $turmas = LegacySchoolClass::query()->where('ativo', 1)->count();

        $this->info('✅ Setup municipal genérico concluído.');
        $this->line('----------------------------------------');
        $this->line("🏫 Escolas:                {$schools}");
        $this->line("📘 Cursos:                 {$courses}");
        $this->line("📚 Séries:                 {$grades}");
        $this->line("🎒 Turmas ativas:          {$turmas}");
        $this->line('----------------------------------------');
        $this->line(sprintf('⏱ Tempo: %.2f s', $executionTime));
        $this->line('Próximo: conferir Educacenso, pacote de relatórios e iniciar matrículas.');
    }
}
