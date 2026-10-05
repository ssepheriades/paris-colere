<template>
  <div class="home">
    <section class="hero" aria-labelledby="home-brand">
      <div class="hero-atmosphere" aria-hidden="true" />

      <div class="hero-inner">
        <p id="home-brand" class="brand reveal">
          Paris Colère
        </p>

        <h1 class="headline reveal reveal-delay-1">
          Qui décide à Paris,<br>
          et sur quelles affaires.
        </h1>

        <p class="lead reveal reveal-delay-2">
          Un dossier public qui relie élus, mandats et controverses municipales —
          pour suivre les responsabilités, pas les rumeurs.
        </p>

        <div class="cta-group reveal reveal-delay-3">
          <v-btn
            color="primary"
            size="large"
            to="/people"
            variant="flat"
          >
            Voir les personnes
          </v-btn>

          <v-btn
            color="secondary"
            size="large"
            to="/affaires"
            variant="outlined"
          >
            Voir les affaires
          </v-btn>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
  .home {
    min-height: 100%;
  }

  .hero {
    position: relative;
    display: flex;
    align-items: center;
    min-height: calc(100dvh - 64px);
    overflow: hidden;
    padding: clamp(2.5rem, 6vw, 5rem) clamp(1.25rem, 5vw, 4rem);
  }

  .hero-atmosphere {
    position: absolute;
    inset: 0;
    background:
      radial-gradient(ellipse 70% 55% at 12% 18%, rgb(122 31 43 / 14%), transparent 60%),
      radial-gradient(ellipse 55% 45% at 88% 78%, rgb(63 58 54 / 8%), transparent 55%),
      linear-gradient(165deg, #f6f1ea 0%, #efe6d8 48%, #f6f1ea 100%);
  }

  .hero-atmosphere::after {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0.35;
    background-image:
      linear-gradient(rgb(28 25 23 / 4%) 1px, transparent 1px),
      linear-gradient(90deg, rgb(28 25 23 / 4%) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(ellipse 75% 70% at 50% 40%, #000 20%, transparent 75%);
  }

  .hero-inner {
    position: relative;
    z-index: 1;
    max-width: 40rem;
  }

  .brand {
    margin: 0 0 1.25rem;
    color: #7a1f2b;
    font-family: Fraunces, serif;
    font-size: clamp(2.75rem, 8vw, 4.5rem);
    font-weight: 600;
    letter-spacing: -0.03em;
    line-height: 1;
  }

  .headline {
    margin: 0;
    color: #1c1917;
    font-family: Fraunces, serif;
    font-size: clamp(1.5rem, 3.6vw, 2.15rem);
    font-weight: 500;
    letter-spacing: -0.02em;
    line-height: 1.2;
  }

  .lead {
    margin: 1.25rem 0 0;
    max-width: 32rem;
    color: #3f3a36;
    font-size: clamp(1.05rem, 2vw, 1.2rem);
    line-height: 1.5;
  }

  .cta-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 2rem;
  }

  .reveal {
    animation: rise-in 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
  }

  .reveal-delay-1 {
    animation-delay: 0.1s;
  }

  .reveal-delay-2 {
    animation-delay: 0.2s;
  }

  .reveal-delay-3 {
    animation-delay: 0.32s;
  }

  @keyframes rise-in {
    from {
      opacity: 0;
      transform: translateY(0.85rem);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .reveal {
      animation: none;
    }
  }
</style>
