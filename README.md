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
- Use ambiente local para testes antes de publicar em producao.
- Scripts de reset/seed de dados nao fazem parte do pacote de producao.
