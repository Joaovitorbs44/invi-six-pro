# Campos outlined e validação antes de publicar

> Histórico da implementação anterior. O padrão atual usa labels acima dos campos, sem fieldset/legend. Consulte [VISUAL-FIXES.md](VISUAL-FIXES.md) para a correção que substitui este visual.

Os 36 inputs de texto, selects e textareas usam um único padrão compartilhado em `public/assets/css/forms-responsive.css`. Inputs ocultos e checkboxes mantêm sua semântica original.

```html
<div class="ui-field h-12">
  <fieldset class="ui-field-outline">
    <legend><label class="ui-field-label" for="campo">Label permanente</label></legend>
    <input id="campo" name="campo" placeholder="Digite o valor">
  </fieldset>
</div>
```

O contêiner mede 48px. O fieldset nativo começa 8px acima dele para acomodar a metade superior do legend; a borda visível mede 48px. O legend interrompe a borda, sem fundo na label. O texto usa 14px, a label usa 12px, o raio é 6px e o padding horizontal é 12px mais a borda de 1px. A posição da label não muda nos estados normal, hover, focus, erro, preenchido e desabilitado. Mensagens de ajuda e erro ficam fora da borda. Textareas começam no mesmo tamanho e podem ser ampliados verticalmente.

As cores vêm dos tokens existentes: fundo `#171C26`, borda `#343E4E`, texto `#F9FAFB`, label `#AAB3C2` e erro/foco `#FF3D2C`. O fieldset desenha a única borda; os controles internos não desenham borda, outline ou ring. As regras compartilhadas anulam estilos antigos das páginas. Labels continuam associadas por `for`/`id`, e mensagens preservam `aria-describedby`. Os seletores de estado desabilitado verificam o controle, sem confundir uma option desabilitada com um select desabilitado.

## Arquivos para atualizar no GitHub

- `package.json`
- `public/assets/css/forms-responsive.css`
- `public/assets/js/app.js`
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

Adicionar estes arquivos novos:

- `scripts/verify.cjs`
- `docs/OUTLINED-FIELDS.md`

`public/assets/css/tailwind.css` foi regenerado pelo build. Continua ignorado pela regra já existente no Git; não precisa ser enviado, pois a Vercel executa o build. Não há novas dependências nem alterações no lockfile.

## Validação

Comandos individuais executados nesta entrega:

- Lint PHP: 80 arquivos aprovados.
- Sintaxe JavaScript: 17 arquivos aprovados, incluindo a rotina nova.
- `php tests/check.php`: 34 verificações aprovadas, sem conexão ao banco.
- `php tests/csrf-reject.php`: CSRF inválido rejeitado com HTTP 419.
- `node tests/routes.cjs http://127.0.0.1:8088 --php-development`: 34 verificações HTTP aprovadas em servidor local, sem chamar endpoints do banco.
- `npm run build`: aprovado, com 320 verificações, incluindo associação das labels aos 36 controles outlined.

A rotina integrada foi preparada com estes comandos:

```text
npm run lint
npm test
npm run verify
```

`npm run verify` reúne lint, testes e build, para na primeira falha e não contém operações de commit ou deploy. Em 12/10/2026, a rotina completa passou após autorização para executá-la fora do sandbox, que inicialmente bloqueou o processo PHP com `spawnSync php EPERM`: 80 PHP e 17 JavaScript no lint, 34 verificações funcionais, rejeição de CSRF inválido, 34 verificações HTTP e build com 320 verificações. Nenhuma variável de ambiente foi modificada.

A captura de referência não estava disponível nesta conversa. As proporções descritas foram implementadas; a comparação visual em navegador com a imagem permanece pendente.

## Publicação com confirmação

Repositório informado: https://github.com/Joaovitorbs44/invi-six-pro. A integração da Vercel com a branch `main` foi informada pelo usuário e não foi modificada.

As integrações GitHub e Vercel foram encontradas, mas não estavam instaladas/conectadas nesta sessão. É preciso instalá-las/conectá-las e autorizar o repositório e o projeto. Depois, verificar se as ferramentas disponíveis permitem criar commits e enviar somente os arquivos desta alteração sem Git local, e se a Vercel permite consultar o status dos deploys. Não solicitar tokens em texto nem incluir credenciais ou `.env` no commit.

Após todas as verificações passarem e o envio estar disponível, revisar o diff e preparar somente os arquivos listados acima, com sugestão de título `Padroniza campos outlined e adiciona validação antes de publicar`. Solicitar confirmação explícita antes de enviar à `main`. Somente após a confirmação, enviar o commit e consultar o status do deploy se houver acesso autorizado. Uma falha nas verificações bloqueia a publicação. Sem acesso à Vercel, informar que o status não foi verificado.

Esta cópia local não contém `.git`, e Git e `gh` não estão disponíveis no PATH. Os 18 arquivos da lista foram conferidos e estão presentes; a lista não inclui `.env`, credenciais, dependências locais ou arquivos temporários. A publicação permanece pendente da instalação/conexão de uma integração GitHub que permita escrita no repositório e da confirmação explícita antes do envio à `main`. A integração Vercel é necessária para acompanhar o deployment por aqui. Nenhum commit foi criado ou enviado nesta entrega, e nenhum deploy foi iniciado.
