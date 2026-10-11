# Relatório de migração — 11/10/2026

## Escopo e preservação

Todas as alterações ocorreram em `D:\Invi-six-pro`. O legado foi somente referência. `.env`, `src/config/env.php` e `src/config/database.php` do novo projeto não foram modificados. Não foram abertos arquivos de credenciais, configurações privadas ou bancos de dados do legado. O inventário cobre a estrutura; os arquivos funcionais selecionados foram revisados, enquanto arquivos sensíveis foram excluídos da leitura de conteúdo.

O projeto novo continha páginas de app, mas faltavam `head.php`, autenticação, regras competitivas, páginas públicas, assets e tabelas; o router apontava para `src/pages` inexistente e seu autoload usava maiúsculas incompatíveis com os nomes reais em Linux. O CSS referenciado pelo header também não era o arquivo gerado pelo build.

O [inventário do legado](inventario-legado.csv) registra 284 arquivos e a decisão de reaproveitamento/exclusão estrutural, sem valores privados.

## Estrutura final

```text
api/                  entrada serverless e php.ini
public/assets/        único diretório público de CSS, JS, imagens e ícones
src/
  app/                páginas/endpoints existentes
  auth/               login, logout, solicitação e ativação
  admin/              administração existente
  components/         componentes visuais reutilizados
  config/             Env, Database, mapas e sessão
  database/           conexão e SQLs revisáveis
  includes/           regras de autenticação, lobby, partidas, ranking e suporte
  pages/home.php      landing do legado adaptada
  routes.php          lista explícita de rotas
scripts/              criação explícita do primeiro administrador
tests/                verificações locais sem acesso ao banco
docs/                 relatório e inventário
router.php            entrada local
vercel.json           runtime PHP, build e rotas
```

## Reaproveitamento

| Origem no legado | Destino/decisão |
| --- | --- |
| `index.php` | `src/pages/home.php`, caminhos corrigidos |
| `auth/*`, `admin/*` | fluxos em `src/auth`/`src/admin`, consultas PostgreSQL e transações adaptadas |
| `components/*` | componentes em `src/components`; navegação completa, tema claro exigido pelo novo projeto |
| `includes/auth`, `account-setup`, `matches`, `matchmaking`, `seasons`, `profile`, `support`, `punishments` e apresentação | regras reaproveitadas, SQL convertido em fonte, PDO nativo |
| `includes/postgres-sessions.php` | handler reaproveitado com configuração `DB_*` atual e conexão independente |
| `public/assets/*` | assets públicos reaproveitados; Tailwind antigo e CSS de simulação excluídos |
| `assets/*` | duplicação legada não copiada |
| `database/postgresql.sql` | base do schema revisado, sem conversão automática de dados legados |
| bancos SQLite, `storage`, configurações privadas, scripts Neon/importação/backup/scheduler | não copiados nem executados |
| ferramentas antigas de frontend/build | substituídas pelo CLI Tailwind 4 já previsto no projeto novo |

As páginas originais de `src/app` foram mantidas e corrigidas; não substituídas por outra aplicação. As imagens dos mapas e ranks, logotipo, cards, toasts, navegação, formulários e layouts do legado foram reaproveitados. O tema é claro, com `#FF3D2C`/`#E93425`, conforme o pedido. Comparação visual em navegador permanece pendente.

Arquivos preexistentes alterados no novo projeto: `api/index.php`, `router.php`, `vercel.json`, `package.json`, `package-lock.json`, `.gitignore`, `public/assets/css/input.css`, `src/components/header.php`, `src/includes/functions.php`, `src/includes/csrf.php` e `src/app/{join,leave,matchmaking,match,match-action,match-snapshot,profile}.php`. Foram adicionados os demais módulos/SQLs/assets/documentação/testes descritos na estrutura. `composer.json` foi removido. `src/app/{history,ranking,support,support-action,support-snapshot,punishments}.php`, mapas, Env, Database e `.env` foram preservados.

## Mudanças principais

- Router local/serverless com rotas explícitas; arquivos internos/credenciais não são servidos. Imports diretos dos arquivos atuais evitam problema de capitalização em Linux.
- PDO atual preservado; contas e competitivo compartilham uma transação. Dialeto SQLite convertido nos arquivos, sem classe de tradução SQL em runtime.
- Lock compartilhado por transação para entradas, saídas, ações, aprovação/rejeição, punições e administração; timeouts locais suportam o Transaction Pooler.
- Ready check para partidas reais, prazo 60 segundos, recusa/expiração e bloqueio da fila durante formação. Ordem do draft corrigida incluindo capitães.
- Resultado com confirmação adversária e aplicação única de +5/-3; ranking por temporada, sem alteração automática dos ranks.
- Sessões/rate limits no PostgreSQL; cookies seguros em HTTPS; CSRF; renovação de sessão/token no login; revogação das sessões após reset de senha; mensagens de login genéricas; verificação de conta ativa e permissões no backend.
- CSP, cabeçalhos, redirects locais validados, escape HTML e validação de IDs; erros internos retornam mensagens genéricas, sem logs de senhas/credenciais.
- Tailwind 4 compilado em `tailwind.css`; caminhos de `@source` corrigidos. JavaScript do painel administrativo separado do template; links CSS repetidos no footer retirados.
- SQLs revisáveis para integridade, catálogo de mapas e isolamento da Data API. Nenhuma migração é aplicada automaticamente.
- Runtime comunitário declarado em Vercel, php.ini e exclusão de arquivos privados/testes/scripts do deploy.

## Remoções

`composer.json`: dependência de dotenv e autoload desnecessários, incompatíveis com a stack sem Composer. `public/README.md`: arquivo vazio; documentação útil agora no README da raiz, fora do diretório publicado. `testing-flow.php`: stubs de simulação sem funcionalidade de produção. Scripts temporários de migração/revisão criados durante o trabalho: removidos após uso. CSS de simulação e consultas/tabela `draft_bans` não utilizadas pelos fluxos reais: excluídos da versão preparada. Colunas antigas de setup/access-code sem uso não são criadas no novo schema; nenhum dado existente foi apagado.

Não foram removidos arquivos do legado, `.env`, imagens em uso, componentes ou dados reais. CSS do Tailwind antigo foi substituído pelo build Tailwind 4; styles específicos utilizados continuam presentes.

## Testes executados

| Verificação | Resultado |
| --- | --- |
| Sintaxe PHP 8.5.8 | passou em 80 arquivos, incluindo scripts/testes/router |
| Build Tailwind 4.3.3 | passou |
| Sintaxe JavaScript | passou em 14 scripts públicos, incluindo painel administrativo extraído |
| `php tests/check.php` | 32 verificações: escape, redirects, IDs, CSRF válido, datas, exibição de pontos, capacidade de equipes e imagens |
| `node tests/assets.cjs` | 205 referências de assets/includes e declaração do runtime válidas |
| `php tests/csrf-reject.php` | token inválido rejeitado com HTTP 419 |
| `node tests/routes.cjs` com servidor PHP | 29 verificações HTTP: 404 de arquivos internos e paths indevidos, assets e cabeçalhos |
| Credenciais no código público | verificação de padrões sem imprimir valores; único resultado foi o atributo HTML `data-reset-password` com ID de usuário, falso positivo; `.env` excluído da inspeção |

Não foram executados login/logout por HTTP, permissões com usuários reais, consultas no Supabase, DDL, concorrência PostgreSQL, registro/dupla confirmação real de resultado, fechamento real de temporada, testes visuais/responsividade/console ou build/execução Vercel. Não havia servidor PostgreSQL/navegador de teste disponível; o download de ferramentas temporárias foi recusado. Os testes offline não demonstram funcionamento desses fluxos em produção.

## Homologação necessária antes da produção

1. Em Supabase de homologação separado, revisar/aplicar `schema.sql`, `maps.sql`, `security.sql`; criar administrador e dez jogadores de teste.
2. Validar solicitação/aprovação/rejeição/ativação, login correto e incorreto, bloqueio por excesso de tentativas, cookie/CSRF, logout e reset de senha revogando sessão anterior. Jogador comum não pode executar endpoints da staff.
3. Disparar joins simultâneos e repetidos, saída durante formação, ready concorrente/recusa/expiração. Confirmar um lobby, dez participantes, ausência de duplicatas e prazo correto.
4. Completar votação, oito picks, veto e escolha de lados. Repetir ações com versão antiga, usuário externo e ID inválido; devem falhar sem alterar o estado.
5. Enviar resultado, confirmar duas vezes e em paralelo; pontuação deve ser aplicada uma única vez. Contestar e resolver como staff; testar cancelamento sem pontos.
6. Verificar ranking com jogadores sem partidas, +5/-3, rank visual preservado, fim de 30 dias, snapshot final e bloqueio de encerramento com partidas pendentes.
7. Validar suporte e punições (incluindo remoção da fila), autorização de conversas e perfis, interface mobile/desktop, teclado e console sem erros.
8. Repetir em Vercel Preview, comprovando PHP/pdo_pgsql, TLS/pooler, sessões entre instâncias e assets. Promover apenas depois de autorização explícita.

## Limites da entrega

A implementação e configuração estão preparadas para homologação; o sistema não foi certificado como pronto para produção. Nenhuma publicação foi feita. Os processos de timeout/temporadas são reconciliados na próxima requisição, não em execução contínua. Temporadas vencidas com resultados pendentes aguardam resolução pela staff. Ativação de solicitação depende do cookie do navegador original, como no fluxo antigo; não foi inventado envio de e-mail.

A Vercel usa [runtime comunitário PHP](https://github.com/vercel-community/php), não suporte nativo. O mantenedor documenta PHP 8.5 e extensões PostgreSQL/mbstring; configuração documental não substitui teste de preview. [Limites dos runtimes Vercel](https://vercel.com/docs/functions/runtimes).

Variáveis, execução local, sequência dos SQLs, bootstrap do administrador e deploy estão em [README.md](../README.md). Os arquivos de configuração atuais e o `.env` permanecem preservados.
