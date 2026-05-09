Projeto: Controle de Estoque Fibra
===============================

Visão rápida
------------
Aplicação PHP simples para gestão de equipamentos, técnicos, movimentações, lembretes e recoletas.

Atualizações aplicadas
- `config/bootstrap.php` criado para carregar `.env` e ajustar configurações para produção
- `config/database.php` atualizado para suportar variáveis de ambiente
- `scripts/seed_database.php` adicionado (seed de exemplo)
- `scripts/undo_seed.php` adicionado (remove seed identificável)

Como rodar localmente
---------------------
Pré-requisitos: PHP (XAMPP), MySQL com banco `controle_estoque_fibra` configurado.

1) Copie exemplo de ambiente:
```powershell
copy .env.example .env
```
Edite `.env` com credenciais de banco.

2) Inicie servidor local (opcional):
```powershell
Set-Location 'c:\xampp\htdocs\controle-estoque-fibra'
C:\xampp\php\php.exe -S 127.0.0.1:8090 -t .
```

3) Popular com dados de exemplo (opcional):
```powershell
C:\xampp\php\php.exe scripts/seed_database.php
```

4) Desfazer seed (se necessário):
```powershell
C:\xampp\php\php.exe scripts/undo_seed.php
```

Checklist rápido para colocar em produção
----------------------------------------
- Definir `APP_ENV=production` em `.env`.
- Configurar variáveis `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` no `.env`.
- Servir por PHP-FPM + Nginx/Apache com HTTPS (garanta `session.cookie_secure=1`).
- Revisar `config/bootstrap.php` Content-Security-Policy conforme fontes externas.
- Fazer backup do banco antes de migrar (export SQL).
- Executar testes manuais de fluxos críticos: criar técnico, entregar equipamento, uso, devolução, recolhimento.

Próximos passos (posso executar)
--------------------------------
- Auditar e corrigir bugs reportados específicos (me diga prioridades).
- Refatorar views repetidas para `views/partials` e consolidar JS repetido.
- Implementar testes automatizados de smoke (scripts simples em `scripts/`).

Se quiser, começo pela consolidação de views e remoção de trechos repetidos.
# Sistema de Controle de Estoque - Provedor Fibra

Sistema web em PHP para controle de estoque e movimentacoes de materiais de campo.

## Requisitos
- XAMPP (Apache + MySQL)
- PHP 8+
- MySQL 8+

## Instalacao
1. Copie o projeto para `C:/xampp/htdocs/controle-estoque-fibra`.
2. Inicie Apache e MySQL no XAMPP.
3. Importe `database/schema.sql` no banco.
4. Se o banco ja existia, rode as migracoes em `database/` que ainda nao foram aplicadas.
5. Ajuste `config/database.php` se necessario.
6. Acesse `http://localhost/controle-estoque-fibra`.

## Rotas Principais
- `index.php?route=dashboard`: gestao de equipamentos e tecnicos
- `index.php?route=movimentacoes`: operacoes de entrega, uso, uso_teste, devolucao e recolhimento
- `index.php?route=testes`: monitoramento de equipamentos em uso_teste com filtros e prioridade
- `index.php?route=relatorios`: visao analitica e cards por tecnico
- `index.php?route=inadimplencia`: importacao e gestao de recolhimento por inadimplencia
- `index.php?route=tecnico_historico&tecnico_id={id}`: historico detalhado por tecnico

## Regras de Movimentacao
- `entrega`: reduz estoque e aumenta saldo na mao do tecnico
- `uso`: reduz saldo na mao do tecnico
- `uso_teste`: reduz saldo na mao e gera alerta com vencimento em 3 dias (domingo passa para segunda)
- `devolucao`: reduz saldo na mao e devolve ao estoque
- `recolhimento`: devolve ao estoque sem passar por saldo na mao

## Estrutura
- `controllers/`: regras de entrada e fluxo
- `models/`: regras de negocio e consultas
- `views/`: telas
- `assets/`: CSS e JS
- `database/`: schema e migracoes

## Observacoes
- O registro em lote esta disponivel na tela de movimentacoes para todos os tipos.
- Na aba de inadimplencia, use uma planilha com cabecalhos no padrao: TITULAR, EQUIPAMENTO, CONTATO, ENDERECO, PRAZO, STATUS, TENTATIVA 1.
- Para agrupamento geografico na inadimplencia, configure a variavel de ambiente `GOOGLE_MAPS_API_KEY` (Geocoding API habilitada). Sem chave, o sistema usa agrupamento por bairro/endereco.
- Use ambiente local para testes antes de publicar em producao.
- Scripts de reset/seed de dados nao fazem parte do pacote de producao.
