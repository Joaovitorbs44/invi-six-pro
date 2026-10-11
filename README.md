# INVI-SIX PRO

PHP puro, PDO PostgreSQL/Supabase, Tailwind CSS 4 e JavaScript vanilla. O legado `D:\LobbyR6S` foi usado como referência e não é necessário para executar este projeto.

## Executar localmente

Requer PHP 8.3+ com `pdo_pgsql`, `mbstring` e sessões; Node.js e npm para compilar CSS.

```powershell
npm ci
npm run build
php -S localhost:8080 -t public router.php
```

O `.env` existente é preservado. [Variáveis de exemplo](.env.example): `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; `APP_ENV` identifica o ambiente. Use os valores do **Transaction Pooler** do Supabase. PDO usa TLS (`sslmode=require`), prepares emulados e conexões não persistentes, sem depender de prepared statements entre transações. O backend não usa Supabase Auth ou Data API.

## Banco e primeiro acesso

Não há migração automática na inicialização. Em um banco de homologação vazio, revise e aplique, nesta ordem:

1. [schema.sql](src/database/schema.sql): tabelas, unicidade, índices, referências e `users.session_version`.
2. [maps.sql](src/database/maps.sql): catálogo real de nove mapas com imagens, sem substituir escolhas existentes.
3. [security.sql](src/database/security.sql): RLS e revogação de acesso público/anon/authenticated às tabelas do backend.

Em banco existente, compare o schema antes de aplicar: `IF NOT EXISTS` não corrige colunas incompatíveis; chaves estrangeiras e índices podem rejeitar dados inconsistentes. `security.sql` altera permissões de tabelas existentes e deve ser revisado caso sejam compartilhadas com outros aplicativos. O schema sem e-mail remove as colunas `users.email` e `access_requests.email` em instalações existentes, descartando esses valores e seus índices. Essa remoção é intencional; revise antes de aplicar. Nada foi executado no Supabase durante esta entrega. Não há importação de SQLite, contas ou dados do legado.

A conexão PHP precisa de um papel autorizado a acessar essas tabelas diretamente. O papel PostgreSQL do Supabase normalmente disponível pelo pooler atende a isso; um papel restrito precisa de privilégios e políticas explícitos. Nunca conceda acesso dessas tabelas a `anon`/`authenticated`.

Para criar o primeiro administrador, após autorização para gravar no banco escolhido, defina `INVI_ADMIN_PASSWORD` privadamente no ambiente do processo e execute:

```powershell
php scripts/create-admin.php --create "Seu nome" "SeuNick"
```

O script não registra nem exibe a senha e não altera `.env`. Remova a variável do processo depois. O administrador pode iniciar uma temporada em `/admin/seasons`. Jogadores solicitam acesso em `/register`; a staff aprova, e o jogador ativa a conta com nick R6, código de ativação e nova senha em `/primeiro-acesso/senha`. O código é exibido no acompanhamento da solicitação; guarde-o em local privado. A recuperação de acesso é realizada pela staff com permissão de administrador no painel, sem e-mail.

## Comportamento competitivo

- Fila única para dez jogadores reais, com bloqueio durante a formação da partida.
- Ready check de 60 segundos antes da votação de capitães; recusa/expiração cancela sem pontos e libera os participantes para entrar novamente.
- Capitães ocupam uma vaga em cada equipe. A formação segue 1–2–2–2–2–1 contando essas vagas; oito escolhas restantes: B, A, A, B, B, A, A, B.
- Veto alternado até sobrar um mapa; capitão A escolhe ataque/defesa.
- Resultado informado por um capitão e confirmado pelo adversário; contestação encaminha à staff. Pontos são aplicados uma vez: +5/-3.
- Temporadas de 30 dias, pontuação inicial zero e rank R6 apenas visual. Classificação final é congelada; uma temporada com partidas pendentes não encerra até a staff resolvê-las.
- Suporte, perfil, punições, histórico e revisão de solicitações conservam os fluxos do legado.

Escritas competitivas usam a mesma conexão PDO e `pg_advisory_xact_lock` transacional. Sessões usam conexão independente, armazenamento PostgreSQL e bloqueio por sessão; limites de tentativas também são compartilhados no PostgreSQL. Mudança de senha invalida sessões antigas por versão.

Prazos são reconciliados nas requisições: sem tráfego, o estado será atualizado na próxima requisição, respeitando o horário original de expiração. Não há processo PHP contínuo, cron obrigatório nem armazenamento permanente em arquivos locais. Polling de partidas fica em quatro segundos; rankings/histórico usam intervalo maior.

## Verificações

```powershell
php tests/check.php
php tests/csrf-reject.php
node tests/assets.cjs
php -S 127.0.0.1:8088 -t public router.php
# Em outro terminal:
node tests/routes.cjs
```

Resultados realmente executados e limites: [relatório da migração](docs/MIGRACAO.md). Os testes de funções/arquivos/rotas não substituem homologação de login, PostgreSQL, concorrência e navegador.

## Deploy na Vercel

PHP depende do runtime comunitário [vercel-php](https://github.com/vercel-community/php), fixado em `0.9.0`, cujo projeto documenta PHP 8.5, `pdo_pgsql` e `mbstring`. A [Vercel documenta runtimes comunitários](https://vercel.com/docs/functions/runtimes). Isso fundamenta a configuração, mas o runtime ainda precisa ser comprovado em preview deste aplicativo.

1. Prepare um Supabase de homologação, revise/aplique os SQLs e configure o primeiro administrador.
2. Na Vercel escolha preset **Other**, diretório raiz deste projeto, instalação `npm ci --include=dev`, build `npm run build`, Output Directory `public` definido pelo `vercel.json`. A instalação inclui o compilador Tailwind, mesmo com `NODE_ENV=production`; as rotas dos assets são relativas à saída pública.
3. Configure as cinco variáveis `DB_*` nos ambientes apropriados e `APP_ENV=preview` ou `production`. Não envie `.env`. Use bancos distintos para preview e produção.
4. Gere um deploy **Preview** autorizado pela interface/CLI da Vercel. O runtime não suporta `vercel dev`; use o servidor PHP local.
5. Confirme nos logs do preview a carga de `pdo_pgsql`/`mbstring`, a conexão TLS/pooler e persistência de sessão entre instâncias. Não publique `phpinfo` ou credenciais para esse teste.
6. Execute o roteiro de homologação do relatório, inclusive concorrência com dez contas de teste. Só promova a produção após esses testes e autorização explícita.

Não houve publicação, execução do runtime remoto, alteração de credenciais ou gravação no banco real. A configuração está preparada para avaliação em preview, e não certificada para produção.
