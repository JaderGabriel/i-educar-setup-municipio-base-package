# Uso — `ieducar:setup-municipio-base`

## Comando

```bash
php artisan ieducar:setup-municipio-base [opções]
```

## O que faz (resumo)

1. `SeederMaster` — cadastros nacionais (localidades, BNCC, configuração escolar base, AEE, calendário genérico).
2. `SetupCadastroComumService` — infantil, metadados Educacenso, sequência de enturmação.
3. `MunicipioGenericoSetupSeeder` — identidade, regra quantitativa, CSV de escolas (se existir), pipeline municipal, trimestres, turmas do ano vigente.
4. **Auditoria** (`ieducar:revisar-cursos-fluxo` com correção automática).
5. **`tipo_boletim`** em turmas sem modelo definido.

## Opções CLI

| Opção | Efeito |
|-------|--------|
| `--skip-master` | Pula `SeederMaster` e cadastro comum (só seeder genérico) |
| `--infantil=` | `bercario-g1-g5` (padrão) ou `bncc-4-etapas` |
| `--ano=` | Ano letivo (auditoria, turmas, trimestres) |
| `--sem-turmas` | Não gera turmas regulares (mantém AEE) |
| `--skip-auditoria` | Não audita cursos/fluxo ao final |
| `--skip-tipo-boletim` | Não define `tipo_boletim` |
| `--auditoria-sem-corrigir` | Só relata problemas na auditoria |
| `--dry-run` | Simula auditoria e boletim sem gravar |
| `--force-production` | Permite em `APP_ENV=production` |

## Variáveis `.env`

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `IEDUCAR_SETUP_INSTITUICAO_ID` | todas | `cod_instituicao` alvo |
| `IEDUCAR_SETUP_INFANTIL_PADRAO` | `bercario-g1-g5` | Padrão de Educação Infantil |
| `IEDUCAR_MUNICIPIO_BASE_NOME_INSTITUICAO` | Secretaria genérica | Nome da instituição |
| `IEDUCAR_MUNICIPIO_BASE_CIDADE` | Município Genérico | Cidade |
| `IEDUCAR_MUNICIPIO_BASE_UF` | BR | UF |
| `IEDUCAR_MUNICIPIO_BASE_CEP` | 00000000 | CEP sede |
| `IEDUCAR_MUNICIPIO_BASE_IBGE` | 0000000 | Código IBGE (CSV escolas) |
| `IEDUCAR_MUNICIPIO_BASE_PREFEITO` | — | Rodapé relatórios |
| `IEDUCAR_MUNICIPIO_BASE_SECRETARIO_EDUCACAO` | — | Rodapé / responsável |
| `IEDUCAR_MUNICIPIO_BASE_ATO_SECRETARIA` | Portaria SME | Rodapé |
| `IEDUCAR_MUNICIPIO_BASE_MEDIA_APROVACAO` | 5.0 | Média mínima |
| `IEDUCAR_MUNICIPIO_BASE_FREQUENCIA_MINIMA` | 75 | % presença |
| `IEDUCAR_MUNICIPIO_BASE_NOME_REGRA_AVALIACAO` | texto curto | `regra_avaliacao.nome` (máx. 50) |
| `IEDUCAR_MUNICIPIO_BASE_ANO_LETIVO` | ano corrente | Ano em andamento |
| `IEDUCAR_MUNICIPIO_BASE_CSV` | `municipio-generico-escolas.csv` | Arquivo de escolas |
| `IEDUCAR_MUNICIPIO_BASE_GERAR_TURMAS` | `true` | Gerar turmas regulares |

## CSV de escolas

Colunas (layout INEP + endereço, igual ao pacote setup):

`inep,nome_escola,cep,logradouro,numero,bairro,zona,...`

Arquivo: `database/seeders/data/municipio-generico-escolas.csv` (neste pacote) ou `database/seeders/Setup/data/` no app.

Se o CSV **não existir**, o setup **continua** com aviso — útil em laboratório; em produção o CSV deve estar presente.

## Exemplos

```bash
# Homologação completa
php artisan ieducar:setup-municipio-base --ano=2026

# Sem turmas (cadastro estrutural apenas)
php artisan ieducar:setup-municipio-base --sem-turmas

# Reaplicar só o seeder municipal (base já seedada)
php artisan ieducar:setup-municipio-base --skip-master

# Produção
php artisan ieducar:setup-municipio-base --ano=2026 --force-production
```

## Pacote setup (`ieducar:setup`)

O pacote `buriti/i-educar-setup-package` (perfis de cidades reais) também executa **auditoria** e **`tipo_boletim`** ao final do `ieducar:setup`, com as mesmas opções `--skip-auditoria`, `--skip-tipo-boletim`, `--auditoria-sem-corrigir` e `--dry-run`.

Para municípios já modelados (Saubara, Amélia Rodrigues, etc.), continue usando `ieducar:setup {slug}`.

## Após o setup

1. Conferir cadastro Educacenso das escolas (campos não preenchidos pelo CSV).
2. Instalar pacote de relatórios (`community:reports:install`).
3. Criar usuários (`ieducar:acessos` no pacote setup, se aplicável).
4. Testar: matrícula → enturmação → nota → boletim.
