# Instalação — `buriti/i-educar-setup-municipio-base-package`

## 1. Adicionar o pacote

### Monorepo / plug-and-play (recomendado neste repositório)

O pacote fica em `packages/buriti/i-educar-setup-municipio-base-package`. Após clonar:

```bash
composer plug-and-play
composer dump-autoload -o
php artisan package:discover --ansi
php artisan optimize:clear
```

### Composer (repositório Git separado)

```json
{
  "require": {
    "buriti/i-educar-setup-municipio-base-package": "^1.0",
    "buriti/i-educar-setup-package": "^1.7"
  }
}
```

```bash
composer update buriti/i-educar-setup-municipio-base-package buriti/i-educar-setup-package
php artisan optimize:clear
```

## 2. Pré-requisitos no app

- Migrations aplicadas (`php artisan migrate`)
- Instituição cadastrada (normalmente `cod_instituicao = 1`)
- Pacote `buriti/i-educar-setup-package` instalado e descoberto

## 3. Configurar `.env` (mínimo)

```env
IEDUCAR_MUNICIPIO_BASE_CIDADE="Seu Município"
IEDUCAR_MUNICIPIO_BASE_UF=BA
IEDUCAR_MUNICIPIO_BASE_IBGE=2900000
IEDUCAR_MUNICIPIO_BASE_ANO_LETIVO=2026
```

Opcional: copiar o CSV de escolas:

```bash
cp packages/buriti/i-educar-setup-municipio-base-package/database/seeders/data/municipio-generico-escolas.csv.example \
   packages/buriti/i-educar-setup-municipio-base-package/database/seeders/data/municipio-generico-escolas.csv
```

Edite o CSV com códigos INEP reais antes de produção.

## 4. Executar

Homologação:

```bash
php artisan ieducar:setup-municipio-base
```

Produção:

```bash
php artisan ieducar:setup-municipio-base --force-production
```

## 5. Relatórios Jasper (separado)

O setup **não** instala o pacote de relatórios. Após o setup:

```bash
php artisan community:reports:install
php artisan reports:jasper-diagnose
```

## 6. Verificar

```bash
php artisan ieducar:revisar-cursos-fluxo --ano=2026
```

O comando `ieducar:setup-municipio-base` já executa auditoria (com correção) e `tipo_boletim` por padrão.
