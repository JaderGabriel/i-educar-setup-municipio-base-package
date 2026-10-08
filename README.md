# buriti/i-educar-setup-municipio-base-package

Pacote **template** de parametrização municipal do i-Educar: um único perfil genérico (`municipio-generico-br`) que percorre o **fluxo completo** (BNCC, calendário, perfis, AEE, turmas, auditoria e `tipo_boletim`).

Diferente do [`buriti/i-educar-setup-package`](../i-educar-setup-package), este pacote **não inclui** seeders de cidades reais (Saubara, Amélia Rodrigues, etc.). Serve como ponto de partida para novos municípios.

## Comando principal

```bash
php artisan ieducar:setup-municipio-base
```

Com `APP_MULTI_TENANT=true`, a cidade e a UF do `.env` precisam ser as da conexão atual. O comando para antes de gravar se a base for outra. Ver [`docs/USO.md`](docs/USO.md).

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| [INSTALL.md](INSTALL.md) | Instalação no i-Educar |
| [docs/USO.md](docs/USO.md) | Uso, variáveis `.env` e opções CLI |
| [docs/FLUXO-COMPLETO.md](docs/FLUXO-COMPLETO.md) | Ordem de execução e o que cada etapa cria |
| [docs/CRIAR-NOVA-CIDADE.md](docs/CRIAR-NOVA-CIDADE.md) | Como derivar um município real a partir deste pacote |

## Dependências

- `buriti/i-educar-setup-package` ^1.7 (serviços compartilhados, auditoria, pipeline municipal)
- Seeders do app hospedeiro: `SeederMaster`, `ConfiguracaoEscolarSeeder`, `PerfisUsuariosMunicipioSeeder`, `GerarTurmasSeeder`, etc.

## Compatibilidade

i-Educar **2.11** e **2.12** · PHP **8.3+**
