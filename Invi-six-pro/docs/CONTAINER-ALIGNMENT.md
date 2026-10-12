# Container global e alinhamento

## Causas encontradas

- O hero usava `max-w-[1920px]` e uma sequência de paddings Tailwind, incluindo `2xl:px-80`.
- Header, footer, comunidade e páginas competitivas tinham limites de 1180px, depois anulados por `max-width:none` e gutters de 20 até 320px.
- A regra global `main > section` acrescentava padding ao section administrativo, enquanto seu wrapper `max-w-7xl` também recebia padding. A área útil era reduzida duas vezes.
- A navegação administrativa tinha seu próprio limite e padding; os cards da landing eram filhos diretos da seção e ainda aplicavam `md:px-10`.
- Folhas carregadas pelas páginas (`competitive.css`, `auth.css`, `matchmaking.css`) mantinham medidas horizontais próprias.

## Estrutura nova

`public/assets/css/layout.css` define `.site-container`: width 100%, max-width 1280px, box-sizing border-box, margin-inline auto e padding lateral de 16px no mobile, 24px a partir de 640px e 32px a partir de 1024px. A classe é aplicada aos wrappers reais; não há containers globais aninhados. Restrições de largura em textos, formulários e modais são intencionais e não controlam o eixo externo das páginas.

O header, hero, cards, demais seções da landing, carrossel, footer, navegação administrativa, comunidade, administração e páginas competitivas compartilham esse container. Imagens e fundos do hero continuam fora dele. O texto do hero limita-se a 540px. Os três cards usam grid com colunas uniformes e o primeiro texto começa no eixo do hero. Em mobile, os cards empilham. O wrapper administrativo ocupa toda a área útil do container, sem padding horizontal adicional no section.

Não foram acrescentadas regras `!important`. As medidas antigas foram removidas na origem.

## Validação e limite atual

`npm run verify` passou: lint de 80 arquivos PHP e 17 JavaScript; 34 verificações funcionais; CSRF inválido rejeitado com HTTP 419; 34 verificações HTTP; build Tailwind e 285 verificações de assets, incluindo o novo CSS de layout.

Foram preparadas cópias temporárias dos templates com dados fictícios fora das pastas públicas para medir `getBoundingClientRect()` em 1920, 1440, 1280, 768 e 390px, sem banco ou autenticação. A execução do Chrome fora do sandbox foi recusada. Portanto, as medições reais, a inspeção visual nas cinco viewports e a confirmação visual das páginas autenticadas **não foram realizadas**. A implementação está preparada para revisão; a validação visual exigida permanece pendente.

Nenhuma alteração no banco, autenticação, rotas, imagens, dependências, `.env` ou configuração de produção. O CSS gerado permanece ignorado pelo Git. Publicação depende de autorização explícita para este novo commit.

## Arquivos do commit

- `public/assets/css/layout.css` (novo)
- `public/assets/css/input.css`
- `public/assets/css/style.css`
- `public/assets/css/forms-responsive.css`
- `public/assets/css/header-design.css`
- `public/assets/css/stage17.css`
- `public/assets/css/stage18.css`
- `public/assets/css/stage19.css`
- `public/assets/css/competitive.css`
- `public/assets/css/auth.css`
- `public/assets/css/matchmaking.css`
- `public/assets/css/pro-carousel.css`
- `src/components/header.php`
- `src/components/footer.php`
- `src/components/admin-navigation.php`
- `src/components/community.php`
- `src/components/pro-teams.php`
- `src/pages/home.php`
- `src/admin/index.php`
- `src/admin/matches.php`
- `src/admin/punishments.php`
- `src/admin/seasons.php`
- `src/app/history.php`
- `src/app/match.php`
- `src/app/matchmaking.php`
- `src/app/profile.php`
- `src/app/punishments.php`
- `src/app/ranking.php`
- `src/app/support.php`
- `src/auth/login.php`
- `src/auth/register.php`
- `docs/CONTAINER-ALIGNMENT.md` (novo)
