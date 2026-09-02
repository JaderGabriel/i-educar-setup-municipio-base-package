# Fluxo completo — setup municipal genérico

Ordem de execução do `php artisan ieducar:setup-municipio-base` e artefatos criados em cada etapa.

## Diagrama

```
SeederMaster (app)
    ├── Localidades IBGE
    ├── Encerrar anos anteriores
    ├── Áreas/disciplinas BNCC
    ├── ConfiguracaoEscolarSeeder (anos, cursos, séries, regras/série/ano, vínculos)
    ├── AeeSeeder (turmas AEE matutino/vespertino)
    ├── CalendarioEscolarSeeder (dias letivos + módulos proporcionais)
    └── Benefícios, raça, religião, transferências

SetupCadastroComumService (pacote setup)
    ├── EducacaoInfantilConfigurator (séries infantil, vínculos)
    ├── CadastroEscolarMetadadosService (idades, Educacenso)
    ├── SequenciaEnturmacaoRevisorService (infantil + fundamental)
    └── GerarSequenciaEnturmacaoCursosSeeder

MunicipioGenericoSetupSeeder (este pacote)
    ├── Identidade institucional + rodapé relatórios
    ├── Regra avaliativa quantitativa (média 5,0, 3 trimestres)
    ├── Módulo Trimestre (3 etapas)
    ├── CSV escolas (opcional)
    ├── Pipeline municipal padrão:
    │     ├── ConfiguracaoEscolarSeeder (reconfirma vínculos)
    │     ├── PerfisUsuariosMunicipioSeeder
    │     ├── MunicipalAeeGeralSeeder → AeeSeeder
    │     ├── SetupCadastroComumService
    │     └── CalendarioEscolarSeeder
    ├── Datas oficiais dos 3 trimestres (ano vigente)
    └── GerarTurmasSeeder (turmas regulares, se habilitado)

SetupPosExecucaoService (pacote setup)
    ├── CursosFluxoAuditoriaService (--corrigir por padrão)
    └── TipoBoletimTurmasService (tipo_boletim por regra/curso)
```

## Critérios atendidos para ano letivo

| Item | Etapa |
|------|--------|
| `escola_ano_letivo` em andamento | ConfiguracaoEscolar + seeder genérico |
| `ano_letivo_modulo` (trimestres) | Calendario + datas municipais genéricas |
| `regra_avaliacao_serie_ano` | ConfiguracaoEscolar + auditoria corrige faltas |
| Vínculos escola-curso-série-disciplina | ConfiguracaoEscolar |
| Sequência de enturmação | SetupCadastroComum |
| Turmas AEE | AeeSeeder |
| Turmas regulares | GerarTurmasSeeder (padrão neste pacote) |
| `tipo_boletim` | TipoBoletimTurmasService |
| Perfis SME/secretaria | PerfisUsuariosMunicipioSeeder |
| Rodapé em relatórios | Identidade institucional |

## O que **não** está no fluxo

- Instalação do pacote Jasper (`community:reports:install`)
- Criação de usuários com CPF real (use seeders de acesso no pacote setup ou manual)
- Ensino Médio completo (`ieducar:ensino-medio`)
- EJA (`ieducar:eja`)
- Importação Educacenso (pacote `buriti/i-educar-educacenso-import-package`)
- Matrículas de teste (`ieducar:matriculas` — **não usar em produção**)

## Mapeamento `tipo_boletim`

| Situação | Valor |
|----------|-------|
| Educação Infantil | Parecer descritivo geral (10) |
| Regra com `parecer_descritivo = 1` | Parecer descritivo geral (10) |
| Regra conceitual (`tipo_nota = 2`) | Boletim conceitual (2) |
| Regra numérica ou mista | Boletim numérico (1) |

## Reexecução

Todos os seeders usam `firstOrCreate` / `updateOrCreate`. Reexecutar o comando é seguro em homologação; em produção use `--force-production` apenas após backup e validação.

Para pular escolas já configuradas: variáveis `CONFIGURACAO_ESCOLAR_FROM_ESCOLA` e `CALENDARIO_SEEDER_FROM_ESCOLA` (documentadas no pacote setup).
