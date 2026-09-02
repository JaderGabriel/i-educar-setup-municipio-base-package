# Como criar um novo município

Este guia descreve a forma recomendada de derivar um perfil municipal **real** a partir do pacote genérico ou do `buriti/i-educar-setup-package`.

## Escolha da abordagem

| Cenário | Abordagem |
|---------|-----------|
| Novo município, primeira vez | Copiar estrutura deste pacote **ou** adicionar seeder no `i-educar-setup-package` |
| Município já listado em `IeducarSetupProfiles` | Usar `ieducar:setup {slug}` existente |
| Laboratório / homologação | `ieducar:setup-municipio-base` com `.env` |

## Passo a passo (novo município no pacote setup)

Recomendado quando o município fará parte da coleção Buriti/Serventec (vários clientes no mesmo repositório).

### 1. Criar pasta de dados

```
packages/buriti/i-educar-setup-package/src/cidades/NN-nome-municipio-uf/
    NomeMunicipioMunicipalData.php
    NomeMunicipioSchoolCsvSynchronizer.php   # se usar CSV
```

Registre o namespace em `composer.json` do pacote setup (bloco `autoload.psr-4` e `classmap` se necessário).

### 2. Definir constantes em `*MunicipalData.php`

Mínimo:

- `NOME_INSTITUICAO`, `CIDADE`, `UF`, `CEP`, `IBGE_MUNICIPIO`
- `MEDIA_APROVACAO`, `NOME_REGRA_AVALIACAO` (máx. 50 caracteres)
- `periodosModuloMunicipal($ano)` — datas dos trimestres/bimestres oficiais
- Textos de rodapé (prefeito, secretário, ato)

Use `MunicipioGenericoMunicipalData` deste pacote como modelo.

### 3. Criar `*SetupSeeder.php`

Em `database/seeders/Setup/`:

```php
class MeuMunicipioSetupSeeder extends Seeder
{
    use MunicipalSetupSupport;

    public function run(): void
    {
        // 1. Identidade + regra + módulo avaliativo
        // 2. (Opcional) CSV escolas
        // 3. $this->runMunicipalStandardPostEscolasPipeline(...)
        // 4. Datas trimestres municipais
        // 5. (Opcional) GerarTurmasSeeder ou continuidade letiva
        // 6. (Opcional) SmeApoioUsersSeeder
    }
}
```

**Padrão de referência:** `CentralBaSetupSeeder` (pipeline completo) ou `AmeliaRodriguesBaSetupSeeder` (rede complexa).

### 4. Registrar o slug

Em `IeducarSetupProfiles::all()`:

```php
'meu-municipio-ba' => [
    'label' => 'Meu Município (BA)',
    'description' => '...',
    'extra_seeders' => [MeuMunicipioSetupSeeder::class],
],
```

### 5. CSV de escolas

Coloque `meu-municipio-ba.csv` em `database/seeders/Setup/data/`.

Layout: ver `AbstractInepMunicipalSchoolCsvSynchronizer` no pacote setup.

### 6. Acessos de usuário

Se houver planilha de servidores:

- Crie `MeuMunicipioSmeApoioUsersSeeder`
- Registre em `IeducarAcessos::accessSeedersForSlug()`

**Sempre** chame `PerfisUsuariosMunicipioSeeder` no pipeline (via `MunicipalSetupSupport`).

### 7. Documentar e testar

```bash
php artisan ieducar:setup meu-municipio-ba --ano=2026
php artisan ieducar:revisar-cursos-fluxo --ano=2026
```

O `ieducar:setup` já roda auditoria + `tipo_boletim` ao final (desde v1.7).

---

## Passo a passo (pacote dedicado a um cliente)

Para um único município em repositório próprio:

1. **Fork** deste pacote `i-educar-setup-municipio-base-package`.
2. Renomeie `MunicipioGenerico*` para o nome do município.
3. Substitua valores padrão em `*MunicipalData` (ou remova `env()` e use constantes).
4. Ajuste `composer.json` (`name`, `description`).
5. Opcional: renomeie o comando para `ieducar:setup-meu-municipio`.

Mantenha a dependência em `buriti/i-educar-setup-package` para reutilizar serviços compartilhados.

---

## Checklist antes de produção

- [ ] Códigos INEP das escolas conferidos com a planilha oficial
- [ ] IBGE do município correto no CSV e na instituição
- [ ] Calendário letivo (trimestres) igual ao documento da SME
- [ ] Regra de avaliação validada pela equipe pedagógica
- [ ] `ieducar:revisar-cursos-fluxo --ano=VIGENTE` sem pendências
- [ ] Turmas com `tipo_boletim` definido
- [ ] `community:reports:install` + teste de boletim
- [ ] Campos Educacenso completos no cadastro da escola
- [ ] Usuários criados e perfis testados
- [ ] Backup do banco antes de `--force-production`

---

## Anti-padrões

- **Não** duplicar `SeederMaster` em seeders municipais sem necessidade.
- **Não** pular `PerfisUsuariosMunicipioSeeder` (Formosa/BA foi corrigido nesse sentido nos perfis novos).
- **Não** assumir que o setup preenche todo o Educacenso.
- **Não** usar `ieducar:matriculas` em produção (dados fictícios).

## Onde pedir ajuda no código

| Necessidade | Classe / comando |
|-------------|------------------|
| Pipeline pós-escolas | `MunicipalSetupSupport` |
| Auditoria | `ieducar:revisar-cursos-fluxo` |
| Tipo boletim | `TipoBoletimTurmasService` |
| Continuidade de anos | `ieducar:continuidade-letiva` |
| Ensino médio | `ieducar:ensino-medio` |

Documentação completa dos comandos: `i-educar-setup-package/docs/COMANDOS-ARTISAN.md`.
