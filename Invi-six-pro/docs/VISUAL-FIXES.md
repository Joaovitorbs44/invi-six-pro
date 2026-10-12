# Correções dos componentes visuais

Os 36 campos passam a usar `.ui-field` com label associada acima do controle. Inputs e selects têm 48px; textareas começam com 48px e permitem expansão vertical. Texto de 14px, label de 12px, padding horizontal de 14px, raio de 6px e uma única borda no controle. Erros mantêm `aria-invalid` e `aria-describedby`, com mensagens abaixo do campo. Os estilos estão centralizados em `forms-responsive.css`.

Foram removidos wrappers fieldset/legend e classes de apresentação conflitantes nos campos. Regras antigas foram removidas dos estilos de autenticação, suporte e etapas anteriores. O teste de assets verifica labels associadas e impede o retorno de fieldset/legend.

Toasts usam largura limitada pela viewport, altura automática, quebra de mensagens longas e nenhuma área de rolagem interna; apenas o toast recebe eventos de ponteiro. Os filtros alinham os controles pela base no desktop e empilham no mobile. Header, navegação administrativa e conteúdo compartilham o gutter responsivo existente, que chega a 320px em telas grandes, sem aplicar esse padding no celular. Botões primários têm texto branco e hover vermelho. O menu do perfil anima a abertura e o fechamento em 200ms, mantém Escape e clique fora e respeita redução de movimento.

## Arquivos do commit

- `public/assets/css/auth.css`
- `public/assets/css/competitive.css`
- `public/assets/css/forms-responsive.css`
- `public/assets/css/header-design.css`
- `public/assets/css/stage18.css`
- `public/assets/css/stage19.css`
- `public/assets/css/style.css`
- `public/assets/css/theme.css`
- `public/assets/js/app.js`
- `src/components/header.php`
- `src/admin/index.php`
- `src/admin/matches.php`
- `src/admin/punishments.php`
- `src/admin/seasons.php`
- `src/app/history.php`
- `src/app/matchmaking.php`
- `src/app/profile.php`
- `src/app/ranking.php`
- `src/app/support.php`
- `src/auth/first-access-password.php`
- `src/auth/login.php`
- `src/auth/register.php`
- `tests/assets.cjs`
- `docs/OUTLINED-FIELDS.md`
- `docs/VISUAL-FIXES.md` (novo)

O CSS Tailwind gerado continua ignorado; não há dependências novas.

## Verificação

`npm run verify` passou: 80 arquivos PHP e 17 JavaScript no lint; 34 verificações funcionais; CSRF inválido rejeitado com HTTP 419; 34 verificações HTTP; build Tailwind e 284 verificações de assets. A diferença de 320 para 284 resulta da remoção das 36 verificações de legends, substituídas pela validação das labels simples já contada por controle.

Foi criada uma página temporária fora das pastas públicas, usando CSS e JavaScript reais para inspecionar componentes sem acessar banco ou endpoints de autenticação. Chrome confirmou altura de 48px, texto de 14px, label de 12px, borda de 1px sem sombra, mensagens abaixo do campo, filtros alinhados no desktop, ausência de overflow da página e dos toasts, e abertura/fechamento do perfil. A captura desktop foi inspecionada visualmente. A execução compacta solicitada em 390px teve viewport interna de 500px por limitação do Chrome; suas medições passaram, mas a captura ficou cortada. A execução adicional com emulação real de 360, 390, 768 e 1920px não foi autorizada. A validação visual nessas larguras e das páginas autenticadas completas permanece pendente. Apenas uma imagem de referência estava disponível na conversa.

Banco, autenticação, rotas, credenciais, `.env` e configurações de produção não foram alterados. O commit deve ser revisado e autorizado antes de atualizar `main`. A permissão anterior de publicação não autoriza o envio desta nova alteração. Acompanhar o deploy apenas pelas permissões disponíveis, respeitando o 403 da Vercel.
