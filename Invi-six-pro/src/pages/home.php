<?php

declare(strict_types=1);
require_once __DIR__ . '/../components/head.php';

?>

<?php require_once __DIR__ . '/../components/header.php'; ?>


<section class="hero18 relative isolate w-full overflow-hidden border-b border-neutral-200 bg-white">

  
  <!-- Imagem principal -->
  <div 
    class="hero18-art pointer-events-none absolute inset-0 z-0 overflow-hidden"
    aria-hidden="true"
    >
    <img
      src="/assets/images/image.png"
      alt=""
      fetchpriority="high"
      class="hero-r6-image absolute object-contain"
    >
  </div>


  <!-- Degradê lateral -->
  <div
    class="hero18-gradient-left pointer-events-none absolute inset-0 z-[1]"
    style="
      background: linear-gradient(
        90deg,
        var(--background) 0%,
        var(--background) 21%,
        rgba(var(--background-rgb),.99) 30%,
        rgba(var(--background-rgb),.96) 37%,
        rgba(var(--background-rgb),.82) 44%,
        rgba(var(--background-rgb),.52) 53%,
        rgba(var(--background-rgb),.20) 63%,
        rgba(var(--background-rgb),0) 78%
      );
    "
    aria-hidden="true">
  </div>

  <!-- Degradê inferior -->
  <div
    class="hero18-gradient-bottom pointer-events-none absolute inset-0 z-[2]"
    style="
      background: linear-gradient(
        0deg,
        var(--background) 0%,
        rgba(var(--background-rgb),.98) 5%,
        rgba(var(--background-rgb),.88) 11%,
        rgba(var(--background-rgb),.60) 19%,
        rgba(var(--background-rgb),.28) 28%,
        rgba(var(--background-rgb),.08) 37%,
        transparent 48%
      );
    "
    aria-hidden="true">
  </div>

  <!-- Quadriculado -->
  <div
    class="grid-bg pointer-events-none absolute inset-0 z-[3] opacity-40"
    aria-hidden="true">
  </div>

  <!-- Partículas existentes -->
  <div
    class="hero-particles pointer-events-none absolute inset-0 z-[3]"
    aria-hidden="true">
  </div>

  <!-- Conteúdo principal -->
  <div class="hero18-content relative z-10 mx-auto flex min-h-[680px] w-full max-w-[1920px] flex-col justify-center px-6 py-24 sm:px-10 lg:min-h-[740px] lg:px-20 xl:px-40 2xl:px-80">

    <div class="flex w-full max-w-[570px] flex-col items-start">

      <!-- Identificação -->
      <div class="mb-7 inline-flex items-center gap-2.5 rounded-lg border border-neutral-200 bg-white/80 px-3.5 py-2 backdrop-blur-sm">

        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#FF3D2C] opacity-30"></span>
          <span class="relative inline-flex h-2 w-2 rounded-full bg-[#FF3D2C]"></span>
        </span>

        <span class="text-[11px] font-medium tracking-[0.01em] text-neutral-600">
          Lobby competitivo
        </span>

        <span class="h-3 w-px bg-neutral-200"></span>

        <span class="text-[11px] text-neutral-500">
          Rainbow Six Siege
        </span>

      </div>

      <!-- Título -->
      <h1 class="max-w-[570px] font-title text-[36px] font-semibold leading-[1.12] tracking-[-0.045em] text-neutral-950 sm:text-[44px] lg:text-[48px]">
        O lobby onde cada
        <span class="block">
          partida vale algo<span class="text-[#FF3D2C]">.</span>
        </span>
      </h1>

      <!-- Descrição -->
      <p class="mt-6 max-w-[430px] text-[13px] leading-[1.85] text-neutral-500 sm:text-sm">
        Entre no lobby, dispute partidas competitivas 5v5 e conquiste seu espaço no ranking. Cada vitória conta durante os 30 dias da temporada.
      </p>

      <?php

      $buttons = [
        [
          'label' => 'Solicitar acesso',
          'href' => '/register',
          'variant' => 'primary',
        ],
        [
          'label' => 'Ver ranking',
          'href' => '/ranking',
          'variant' => 'secondary',
        ],
      ];

      $buttonVariants = [
        'primary' => 'border border-[#FF3D2C] bg-[#FF3D2C] text-white hover:border-[#E93425] hover:bg-[#E93425]',
        'secondary' => 'border border-neutral-200 bg-white/90 text-neutral-900 hover:border-neutral-400 hover:bg-white',
      ];

      ?>

      <!-- Botões -->
      <div class="mt-9 flex w-full flex-wrap items-center gap-3">

        <?php foreach ($buttons as $button) { ?>

          <a
            href="<?= e($button['href']) ?>"
            class="group inline-flex min-h-[46px] items-center justify-center gap-3 rounded-md px-6 text-xs font-medium shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md <?= $buttonVariants[$button['variant']] ?>">

            <span><?= e($button['label']) ?></span>

            <?php if ($button['variant'] === 'primary') { ?>
              <?= faIcon('arrow-right') ?>
            <?php } ?>

          </a>

        <?php } ?>

      </div>

      <!-- Informações do lobby -->
      <div class="mt-14 w-full max-w-[480px] border-t border-neutral-200/80 pt-6">

        <div class="grid grid-cols-3 gap-4 sm:gap-6">

          <!-- Formato -->
          <div class="flex flex-col gap-1.5">
            <span class="font-title text-[21px] font-semibold leading-none tracking-tight text-neutral-950">
              5v5
            </span>
            <span class="text-[10px] leading-relaxed text-neutral-500">
              Partidas competitivas
            </span>
          </div>

          <!-- Temporada -->
          <div class="flex flex-col gap-1.5 border-l border-neutral-200/80 pl-4 sm:pl-6">
            <span class="font-title text-[21px] font-semibold leading-none tracking-tight text-neutral-950">
              30 dias
            </span>
            <span class="text-[10px] leading-relaxed text-neutral-500">
              Duração da temporada
            </span>
          </div>

          <!-- Pontuação -->
          <div class="flex flex-col gap-1.5 border-l border-neutral-200/80 pl-4 sm:pl-6">
            <span class="font-title text-[21px] font-semibold leading-none tracking-tight text-neutral-950">
              +5 pts
            </span>
            <span class="text-[10px] leading-relaxed text-neutral-500">
              Por vitória
            </span>
          </div>

        </div>

      </div>

    </div>

  </div>

  <!-- Ajustes responsivos -->
  <style>
    @media (max-width: 1023px) {
      .hero-r6-image {
        right: -45%;
        width: 150%;
        opacity: .55;
      }
    }

    @media (max-width: 639px) {
      .hero-r6-image {
        right: -85%;
        width: 200%;
        opacity: .22;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .hero-particles,
      .hero-r6-image {
        animation: none !important;
      }
    }
  </style>

</section>

  <!-- Como funciona -->
  <section id="como-funciona" class="landing-section flex w-full bg-white">

    <?php

      $steps = [
        [
          'number' => '01',
          'title' => 'Entre na fila',
          'description' => 'Dez jogadores entram no matchmaking para formar uma nova partida competitiva.',
          'class' => 'border-l border-r border-neutral-200',
        ],
        [
          'number' => '02',
          'title' => 'Monte os times',
          'description' => 'Os capitães fazem os picks e montam os dois times antes do veto de mapas.',
          'class' => 'border-r border-neutral-200',
        ],
        [
          'number' => '03',
          'title' => 'Suba no ranking',
          'description' => 'Vitória vale +5 pontos e derrota vale -3. Todos começam uma nova temporada com zero.',
          'class' => 'border-r border-neutral-200',
        ],
      ];

    ?>

    <?php foreach ($steps as $step): ?>

      <article class="<?= $step['class'] ?> w-full bg-white py-10 md:px-10">

        <span class="text-xs font-medium text-[#FF3D2C]">
          <?= htmlspecialchars($step['number']) ?>
        </span>

        <h2 class="text-lg text-black font-semibold font-title">
          <?= htmlspecialchars($step['title']) ?>
        </h2>

        <p class="mt-3 text-sm leading-6 text-neutral-500">
          <?= htmlspecialchars($step['description']) ?>
        </p>

      </article>

    <?php endforeach; ?>

  </section>

<!-- Ranking -->
<section id="tabela" class="landing-section w-full py-20 border-t border-neutral-200 bg-white">

    <div class="flex items-end justify-between gap-4">

      <div class="flex flex-col gap-2">

        <span class="text-xs font-medium text-[#FF3D2C]">
          - RANKING
        </span>


        <h2 class="text-xl text-black font-medium font-title">
          Cada vitória muda a tabela.
        </h2>

        <p class="text-xs text-neutral-500 leading-relaxed">
          Dispute posições durante toda a temporada. Cada vitória soma pontos e cada derrota pode mudar sua posição no ranking.
        </p>

      </div>

      <a href="/ranking" class="text-xs text-[#FF3D2C] font-medium hover:text-[#E93425] transition">
        Ver ranking completo <?= faIcon('arrow-right') ?>
      </a>

    </div>

    <?php

    require_once __DIR__ . '/../includes/profile.php';
    $currentSeason = competitiveSeason($pdo);
    $ranking = [];
    foreach (competitiveRanking($pdo, $currentSeason['id'] ?? null, 5) as $position => $row) {
      $ranking[] = ['position' => $position + 1, 'player' => $row['r6_nickname'],
        'initial' => mb_strtoupper(mb_substr($row['r6_nickname'], 0, 1)),
        'rank' => $row['r6_rank'], 'wins' => $row['wins'], 'losses' => $row['losses'], 'points' => $row['points']];
    }

    ?>

    <div class="mt-12 flex flex-col gap-3">
      <?php if (!$ranking) { ?><p class="text-xs text-neutral-500">O ranking será exibido quando uma temporada estiver ativa.</p><?php } ?>

      <?php foreach ($ranking as $player) { ?>

        <?php

        $rankImage = matchmakingRankImage($player['rank']);

        ?>

        <article
          class="landing-ranking-row grid items-center rounded-lg border border-neutral-200 bg-white px-6 py-4 transition hover:border-neutral-300">

          <span
            class="text-xs font-semibold <?= $player['position'] === 1 ? 'text-[#FF3D2C]' : 'text-neutral-400' ?>">
            <?= str_pad((string) $player['position'], 2, '0', STR_PAD_LEFT) ?>
          </span>

          <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-100 text-[10px] font-semibold text-black">
              <?= e($player['initial']) ?>
            </div>

            <div>

              <p class="text-xs font-semibold text-black">
                <?= e($player['player']) ?>
              </p>

              <p class="mt-1 text-[10px] text-neutral-400">
                Jogador
              </p>

            </div>

          </div>

          <div>

            <p class="text-[10px] uppercase tracking-wider text-neutral-400">
              Rank R6
            </p>

            <div class="mt-1 flex items-center gap-2">

              <?php if ($rankImage !== null) { ?>

                <img
                  src="<?= e($rankImage) ?>"
                  alt="<?= e($player['rank']) ?>"
                  class="h-5 w-5 object-contain">

              <?php } ?>

              <p class="text-xs font-medium text-black">
                <?= e($player['rank']) ?>
              </p>

            </div>

          </div>

          <div>

            <p class="text-[10px] uppercase tracking-wider text-neutral-400">
              Vitórias
            </p>

            <p class="mt-1 text-xs font-medium text-black">
              <?= e((string) $player['wins']) ?>
            </p>

          </div>

          <div>

            <p class="text-[10px] uppercase tracking-wider text-neutral-400">
              Derrotas
            </p>

            <p class="mt-1 text-xs font-medium text-black">
              <?= e((string) $player['losses']) ?>
            </p>

          </div>

          <div class="text-right">

            <p class="text-[10px] uppercase tracking-wider text-neutral-400">
              Pontos
            </p>

            <p
              class="mt-1 text-sm font-semibold <?= $player['position'] === 1 ? 'text-[#FF3D2C]' : 'text-black' ?>">
              <?= e((string) $player['points']) ?>
            </p>

          </div>

        </article>

      <?php } ?>

    </div>

  </div>

</section>


<section
  id="invitational"
  class="relative isolate w-full overflow-hidden bg-[#111113] text-white"
>

  <!-- Background oficial -->
  <picture class="pointer-events-none absolute inset-0 z-0 block">

    <source
      media="(max-width: 767px)"
      srcset="https://www.eventim.com.br/campaign/fileadmin/fm_br/campaigns/2026/six-invitational/si-mb.jpg"
    >

    <img
      src="https://www.eventim.com.br/campaign/fileadmin/fm_br/campaigns/2026/six-invitational/si-bg.jpg"
      alt=""
      loading="lazy"
      class="h-full w-full object-cover object-center"
    >

  </picture>

  <!-- Overlay lateral -->
  <div
    class="pointer-events-none absolute inset-0 z-[1]"
    style="
      background: linear-gradient(
        90deg,
        rgba(15,15,17,.94) 0%,
        rgba(15,15,17,.82) 25%,
        rgba(15,15,17,.48) 52%,
        rgba(15,15,17,.12) 100%
      );
    "
    aria-hidden="true">
  </div>

  <!-- Overlay vertical -->
  <div
    class="pointer-events-none absolute inset-0 z-[2]"
    style="
      background: linear-gradient(
        180deg,
        rgba(15,15,17,.30) 0%,
        transparent 22%,
        transparent 65%,
        rgba(15,15,17,.70) 100%
      );
    "
    aria-hidden="true">
  </div>

  <!-- Conteúdo -->
  <div
    class="relative z-10 mx-auto w-full max-w-[1920px] px-6 sm:px-10 lg:px-20 xl:px-40 2xl:px-80"
  >

    <div
      class="flex min-h-[680px] flex-col justify-between py-12 md:min-h-[740px] md:py-16 lg:min-h-[780px]"
    >

      <!-- Header -->
      <div class="flex items-center justify-between gap-6">

        <div class="flex items-center gap-3">

          <span class="h-px w-7 bg-[#B8FF45]"></span>

          <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#B8FF45]">
            Rainbow Six Siege
          </span>

        </div>

        <div class="hidden items-center gap-2.5 md:flex">

          <span class="h-1.5 w-1.5 rounded-full bg-[#B8FF45]"></span>

          <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-white/45">
            São Paulo, Brasil
          </span>

          <span class="text-[10px] text-white/25">
            /
          </span>

          <span class="text-[10px] font-medium text-white/45">
            2027
          </span>

        </div>

      </div>

      <!-- Conteúdo principal -->
      <div class="max-w-[700px] py-20 md:py-24">

        <!-- Logo -->
        <img
          src="https://www.eventim.com.br/campaign/fileadmin/fm_br/campaigns/2026/six-invitational/si-logo.svg"
          alt="Six Invitational"
          loading="lazy"
          class="h-auto w-[150px] brightness-0 invert md:w-[180px]"
        >

        <!-- Identificação -->
        <div class="mt-10 flex flex-wrap items-center gap-3">

          <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#B8FF45]">
            Six Invitational 2027
          </span>

          <span class="h-1 w-1 rounded-full bg-white/25"></span>

          <span class="text-[10px] font-medium uppercase tracking-[0.16em] text-white/45">
            Brasil
          </span>

        </div>

        <!-- Título -->
        <h2
          class="mt-5 max-w-[680px] font-title text-[40px] font-semibold leading-[1.05] tracking-[-0.045em] text-white sm:text-[50px] lg:text-[62px]"
        >
          O maior palco do Siege
          <span class="block text-white/55">
            volta ao Brasil<span class="text-[#B8FF45]">.</span>
          </span>
        </h2>

        <!-- Descrição -->
        <p
          class="mt-7 max-w-[480px] text-[13px] leading-[1.9] text-white/55 md:text-sm"
        >
          O Six Invitational retorna a São Paulo, reunindo os melhores
          jogadores e equipes de Rainbow Six Siege no maior campeonato
          da temporada.
        </p>

        <!-- Ações -->
        <div class="mt-9 flex flex-wrap items-center gap-6">

          <a
            href="https://www.eventim.com.br/campaign/six-invitational-27"
            target="_blank"
            rel="noopener noreferrer"
            class="group inline-flex h-12 items-center justify-center gap-8 rounded-md border border-white/20 bg-white/10 px-6 text-[11px] font-semibold text-white backdrop-blur-md transition-all duration-300 hover:-translate-y-0.5 hover:border-white/40 hover:bg-white/15"
          >

            <span>
              Explorar evento
            </span>

            <?= faIcon('arrow-right') ?>

          </a>

          <!-- Informação secundária -->
          <div class="flex items-center gap-2.5">

            <span class="h-1.5 w-1.5 rounded-full bg-[#B8FF45]"></span>

            <span class="text-[10px] font-medium text-white/45">
              Evento presencial
            </span>

          </div>

        </div>

      </div>

      <!-- Informações do evento -->
      <div class="grid grid-cols-2 border-t border-white/15 md:grid-cols-4">

        <!-- Data -->
        <div class="py-6 md:py-7">

          <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-white/40">
            Data
          </span>

          <p class="mt-2.5 font-title text-[17px] font-semibold tracking-tight text-white">
            12–14 Fev
          </p>

        </div>

        <!-- Cidade -->
        <div class="border-l border-white/10 py-6 pl-5 md:py-7 md:pl-7">

          <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-white/40">
            Cidade
          </span>

          <p class="mt-2.5 font-title text-[17px] font-semibold tracking-tight text-white">
            São Paulo
          </p>

        </div>

        <!-- País -->
        <div class="border-t border-white/10 py-6 md:border-l md:border-t-0 md:py-7 md:pl-7">

          <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-white/40">
            País
          </span>

          <p class="mt-2.5 font-title text-[17px] font-semibold tracking-tight text-white">
            Brasil
          </p>

        </div>

        <!-- Ano -->
        <div class="border-l border-t border-white/10 py-6 pl-5 md:border-t-0 md:py-7 md:pl-7">

          <span class="text-[10px] font-medium uppercase tracking-[0.15em] text-white/40">
            Edição
          </span>

          <div class="mt-2.5 flex items-center gap-2">

            <span class="h-1.5 w-1.5 rounded-full bg-[#B8FF45]"></span>

            <p class="font-title text-[17px] font-semibold tracking-tight text-[#B8FF45]">
              2027
            </p>

          </div>

        </div>

      </div>

    </div>

  </div>

</section>



<!-- Acesso -->
<section class="landing-section w-full py-20 bg-white">

  <div class="w-full">

    <div class="max-w-xl">

      <span class="text-xs font-medium text-[#FF3D2C]">
        - ACESSO
      </span>


<h2 class="text-xl text-black font-medium font-title">
        Seu caminho até a primeira partida.
      </h2>

      <p class="mt-4 text-sm leading-6 text-neutral-500">
        O lobby é fechado. A staff analisa cada solicitação antes de liberar o acesso.
      </p>

    </div>

    <div class="relative mt-16">

      <!-- Linha -->
      <div class="absolute left-0 right-0 top-[7px] hidden h-px bg-neutral-200 md:block"></div>

      <div class="relative grid gap-10 md:grid-cols-4">

        <!-- Etapa 1 -->
        <div>

          <div class="relative z-10 h-[15px] w-[15px] rounded-full border-4 border-white bg-[#FF3D2C]"></div>

          <span class="mt-6 block text-[10px] font-medium text-[#FF3D2C]">
            01
          </span>

          <h3 class="mt-3 text-sm font-semibold text-black">
            Solicitação
          </h3>

          <p class="mt-2 max-w-[220px] text-xs leading-5 text-neutral-500">
            Informe seus dados, nick, rank atual e seu perfil no R6 Tracker.
          </p>

        </div>

        <!-- Etapa 2 -->
        <div>

          <div class="relative z-10 h-[15px] w-[15px] rounded-full border-4 border-white bg-[#FF3D2C]"></div>

          <span class="mt-6 block text-[10px] font-medium text-[#FF3D2C]">
            02
          </span>

          <h3 class="mt-3 text-sm font-semibold text-black">
            Análise da staff
          </h3>

          <p class="mt-2 max-w-[220px] text-xs leading-5 text-neutral-500">
            A equipe verifica sua solicitação e as informações da sua conta.
          </p>

        </div>

        <!-- Etapa 3 -->
        <div>

          <div class="relative z-10 h-[15px] w-[15px] rounded-full border-4 border-white bg-[#FF3D2C]"></div>

          <span class="mt-6 block text-[10px] font-medium text-[#FF3D2C]">
            03
          </span>

          <h3 class="mt-3 text-sm font-semibold text-black">
            Receba seu acesso
          </h3>

          <p class="mt-2 max-w-[220px] text-xs leading-5 text-neutral-500">
            Se aprovado, a staff fornecerá sua senha para acessar o lobby.
          </p>

        </div>

        <!-- Etapa 4 -->
        <div>

          <div class="relative z-10 h-[15px] w-[15px] rounded-full border-4 border-white bg-[#FF3D2C]"></div>

          <span class="mt-6 block text-[10px] font-medium text-[#FF3D2C]">
            04
          </span>

          <h3 class="mt-3 text-sm font-semibold text-black">
            Pronto para jogar
          </h3>

          <p class="mt-2 max-w-[220px] text-xs leading-5 text-neutral-500">
            Faça login, entre na fila e dispute sua primeira partida.
          </p>

        </div>

      </div>

    </div>

    <div class="mt-12">

      <a
        href="/register"
        class="inline-flex rounded-[4px] bg-[#FF3D2C] px-8 py-3 text-xs font-medium text-white transition hover:bg-[#E93425]">
        Solicitar acesso
      </a>

    </div>

  </div>

</section>




<?php

require __DIR__ . '/../components/pro-teams.php';
require_once __DIR__ . '/../components/footer.php';
