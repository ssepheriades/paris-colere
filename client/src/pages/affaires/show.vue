<template>
  <v-container class="py-8 py-md-12" max-width="840">
    <v-btn
      class="mb-6"
      prepend-icon="mdi-arrow-left"
      to="/affaires"
      variant="text"
    >
      Affaires
    </v-btn>

    <v-alert
      v-if="store.status === 'error'"
      :text="store.detail ?? 'La fiche est indisponible.'"
      title="Fiche indisponible"
      type="error"
      variant="tonal"
    >
      <template #append>
        <v-btn v-if="controversyId" color="error" variant="text" @click="store.loadOne(controversyId)">
          Réessayer
        </v-btn>
      </template>
    </v-alert>

    <template v-else-if="store.status === 'loading'">
      <v-skeleton-loader class="mb-6 bg-transparent" type="image" />
      <v-skeleton-loader class="bg-transparent" type="article" />
    </template>

    <header v-else-if="store.controversy" class="affair-header">
      <v-img
        v-if="store.controversy.illustration"
        alt=""
        class="affair-illustration"
        cover
        height="280"
        :src="store.controversy.illustration"
      />

      <div v-else class="affair-placeholder">
        <v-icon color="primary" icon="mdi-image-outline" size="36" />
      </div>

      <div v-if="store.controversy.themes.length" class="theme-chips">
        <v-chip
          v-for="theme in store.controversy.themes"
          :key="theme.id"
          :color="theme.color ?? 'secondary'"
          size="small"
          :variant="theme.color ? 'flat' : 'tonal'"
        >
          {{ theme.name }}
        </v-chip>
      </div>

      <h1 class="page-title affair-name">{{ store.controversy.name }}</h1>

      <p v-if="store.controversy.shortDescription" class="affair-summary">
        {{ store.controversy.shortDescription }}
      </p>

      <v-row
        v-if="store.controversy.keyFigures.length"
        class="key-figures"
      >
        <v-col
          v-for="keyFigure in store.controversy.keyFigures"
          :key="keyFigure.id"
          cols="12"
          sm="6"
          md="4"
        >
          <v-card class="key-figure-card" variant="outlined">
            <v-card-text class="key-figure-body">
              <div class="key-figure-value">
                <span
                  v-for="(part, index) in figureParts(keyFigure.figure)"
                  :key="index"
                  :class="{ 'key-figure-accent': part.accent }"
                >{{ part.text }}</span>
              </div>
              <div class="key-figure-label">{{ keyFigure.label }}</div>
              <div class="key-figure-sublabel">{{ keyFigure.subLabel }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <template v-if="store.controversy.items.length">
        <h2 class="text-overline text-medium-emphasis mb-0">Chronologie</h2>

        <v-timeline
          align="start"
          class="affair-timeline"
          density="comfortable"
          line-color="primary"
          side="end"
          truncate-line="both"
        >
          <v-timeline-item
            v-for="item in store.controversy.items"
            :key="item.id"
            dot-color="primary"
            fill-dot
            size="small"
          >
            <v-card class="affair-item-card">
              <v-card-item>
                <v-card-title class="text-h6">
                  {{ item.title }}
                </v-card-title>

                <v-card-subtitle class="affair-item-date">
                  {{ formatItemDate(item.date) }}
                </v-card-subtitle>
              </v-card-item>

              <v-card-text class="pt-0">
                <v-chip class="mb-3" color="primary" size="small" variant="tonal">
                  {{ typeLabel(item.type) }}
                </v-chip>

                <p v-if="item.shortDescription" class="affair-item-description mb-0">
                  {{ item.shortDescription }}
                </p>

                <div v-if="item.sources.length" class="affair-sources">
                  <template v-for="source in item.sources" :key="source.id || source.url">
                    <div v-if="videoEmbedSrc(source) && loadedVideos[source.id]" class="affair-embed">
                      <iframe
                        :src="videoEmbedSrc(source) ?? undefined"
                        :title="source.title || 'Lecteur vidéo'"
                        allow="fullscreen; picture-in-picture"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                      />
                    </div>

                    <button
                      v-else-if="videoEmbedSrc(source)"
                      class="affair-embed-trigger"
                      type="button"
                      @click="loadedVideos[source.id] = true"
                    >
                      <span class="affair-embed-media">
                        <img
                          v-if="httpsUrl(source.thumbnailUrl)"
                          alt=""
                          class="affair-embed-thumb"
                          :src="httpsUrl(source.thumbnailUrl) ?? undefined"
                        >
                        <span class="affair-embed-play">Lire la vidéo</span>
                      </span>
                      <span v-if="source.title" class="affair-embed-caption">{{ source.title }}</span>
                    </button>

                    <div
                      v-else-if="source.provider === 'tweet' && httpUrl(source.url)"
                      class="affair-tweet"
                    >
                      <blockquote class="twitter-tweet" data-dnt="true" data-align="center">
                        <a :href="httpUrl(source.url) ?? undefined">
                          {{ source.embedText || source.authorName || 'Voir le message' }}
                        </a>
                      </blockquote>
                    </div>

                    <p v-else class="affair-source-link">
                      <a
                        v-if="httpUrl(source.url)"
                        class="affair-source-anchor"
                        :href="httpUrl(source.url) ?? undefined"
                        rel="noopener noreferrer"
                        target="_blank"
                      >
                        {{ source.url }}
                      </a>
                    </p>
                  </template>
                </div>
              </v-card-text>
            </v-card>
          </v-timeline-item>
        </v-timeline>
      </template>

      <v-btn
        class="mt-2"
        color="primary"
        variant="outlined"
        @click="contact.open(store.controversy.id)"
      >
        Contacter à propos de cette affaire
      </v-btn>
    </header>
  </v-container>
</template>

<script lang="ts" setup>
  import { computed, nextTick, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import { useContactStore } from '@/stores/contact'
  import { useControversiesStore, type Source } from '@/stores/controversies'

  const route = useRoute()
  const store = useControversiesStore()
  const contact = useContactStore()
  const loadedVideos = ref<Record<string, boolean>>({})

  const controversyId = computed(() => {
    const id = route.params.id
    return typeof id === 'string' ? id : ''
  })

  interface TwitterWidgets {
    ready?: (callback: (api: TwitterWidgets) => void) => void
    widgets: {
      load: (element?: HTMLElement) => void
    }
  }

  let twitterWidgets: Promise<TwitterWidgets | null> | null = null

  function twitterApi (): Promise<TwitterWidgets | null> {
    const current = (window as Window & { twttr?: TwitterWidgets }).twttr
    if (current?.widgets) {
      return Promise.resolve(current)
    }

    twitterWidgets ??= new Promise(resolve => {
      const script = document.createElement('script')
      script.src = 'https://platform.twitter.com/widgets.js'
      script.async = true
      script.charset = 'utf-8'
      script.onload = () => {
        const loaded = (window as Window & { twttr?: TwitterWidgets }).twttr
        if (loaded?.ready) {
          loaded.ready(api => resolve(api.widgets ? api : null))
          return
        }

        resolve(loaded?.widgets ? loaded : null)
      }
      script.onerror = () => resolve(null)
      document.head.appendChild(script)
    })

    return twitterWidgets
  }

  watch(controversyId, id => {
    loadedVideos.value = {}
    if (id) {
      void store.loadOne(id)
    }
  }, { immediate: true })

  watch(() => store.controversy?.items, async items => {
    const hasTweet = items?.some(item => item.sources.some(source => source.provider === 'tweet' && httpUrl(source.url)))
    if (!hasTweet) {
      return
    }

    await nextTick()
    const api = await twitterApi()
    api?.widgets.load()
  })

  function httpUrl (value: string | null): string | null {
    if (!value) {
      return null
    }

    try {
      const url = new URL(value)
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null
    } catch {
      return null
    }
  }

  function httpsUrl (value: string | null): string | null {
    const url = httpUrl(value)
    return url?.startsWith('https://') ? url : null
  }

  function videoEmbedSrc (source: Source): string | null {
    const id = source.externalId
    if (!id) {
      return null
    }

    if (source.provider === 'youtube' && /^[A-Za-z0-9_-]{11}$/.test(id)) {
      return `https://www.youtube-nocookie.com/embed/${id}`
    }

    if (source.provider === 'dailymotion' && /^[A-Za-z0-9]+$/.test(id)) {
      return `https://www.dailymotion.com/embed/video/${id}`
    }

    if (source.provider === 'vimeo' && /^\d+$/.test(id)) {
      return `https://player.vimeo.com/video/${id}`
    }

    return null
  }

  function figureParts (figure: string) {
    return figure.split(/([+\-±−\d]+)/).filter(Boolean).map(text => ({
      text,
      accent: /^[+\-±−\d]+$/.test(text),
    }))
  }

  const typeLabels: Record<string, string> = {
    fact: 'Fait',
    statement: 'Déclaration',
    publication: 'Publication',
  }

  function typeLabel (type: string) {
    return typeLabels[type] ?? type
  }

  function formatItemDate (value: string) {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
    if (!match) {
      return value
    }

    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))

    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(date)
  }
</script>

<style scoped>
  .affair-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .affair-illustration {
    width: 100%;
    border: 1px solid #e7dfd4;
    border-radius: 4px;
  }

  .affair-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 280px;
    background: #f6f1ea;
    border: 1px solid #e7dfd4;
    border-radius: 4px;
  }

  .affair-name {
    max-width: 100%;
    min-width: 0;
    margin: 0.35rem 0 0;
    font-size: clamp(1.75rem, 5vw, 2.75rem);
    line-height: 1.15;
    overflow-wrap: anywhere;
  }

  .affair-summary {
    margin: 0;
    color: #3f3a36;
    font-size: 1.05rem;
    line-height: 1.4;
  }

  .theme-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .key-figures {
    width: 100%;
    margin: 0.25rem -0.75rem 0;
  }

  .key-figures > .v-col {
    padding: 0.75rem;
  }

  .key-figure-card {
    height: 100%;
    border: 2px solid rgb(var(--v-theme-primary));
    background: #fbf8f3;
  }

  .key-figure-body {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .key-figure-value {
    font-size: clamp(1.5rem, 3.5vw, 2rem);
    font-weight: 700;
    line-height: 1.15;
  }

  .key-figure-accent {
    color: rgb(var(--v-theme-primary));
  }

  .key-figure-label {
    margin-top: 0.35rem;
    color: #3f3a36;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.35;
  }

  .key-figure-sublabel {
    margin-top: 0.2rem;
    min-height: 1.3em;
    color: #6b6560;
    font-size: 0.8rem;
    font-weight: 400;
    line-height: 1.3;
  }

  .affair-timeline {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    grid-template-columns: minmax(0, max-content) max-content minmax(0, 1fr);
  }

  .affair-timeline.v-timeline--vertical.v-timeline--side-end:deep(.v-timeline-item > .v-timeline-item__body) {
    justify-self: stretch;
    min-width: 0;
    max-width: 100%;
  }

  .affair-item-date {
    opacity: 1;
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }

  .affair-item-card {
    background: #fffcf8;
    max-width: 100%;
    min-width: 0;
  }

  .affair-item-card :deep(.v-card-title) {
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }

  .affair-item-description {
    color: #3f3a36;
    line-height: 1.55;
    white-space: pre-line;
  }

  .affair-sources {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.9rem;
  }

  .affair-embed,
  .affair-embed-media {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: #2a2420;
    border: 1px solid #e7dfd4;
    border-radius: 4px;
  }

  .affair-embed iframe,
  .affair-embed-thumb {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
    object-fit: cover;
  }

  .affair-embed-trigger {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.55rem;
    width: 100%;
    padding: 0;
    color: inherit;
    background: transparent;
    border: 0;
    cursor: pointer;
    text-align: left;
  }

  .affair-embed-play {
    position: absolute;
    top: 50%;
    left: 50%;
    padding: 0.45rem 0.85rem;
    color: #3f3a36;
    font-weight: 700;
    background: #fffcf8;
    border: 1px solid #e7dfd4;
    border-radius: 999px;
    transform: translate(-50%, -50%);
  }

  .affair-embed-caption {
    color: #3f3a36;
    font-weight: 700;
    line-height: 1.35;
  }

  .affair-tweet {
    display: flex;
    justify-content: center;
    width: 100%;
  }

  .affair-tweet :deep(.twitter-tweet-rendered),
  .affair-tweet :deep(iframe) {
    margin-inline: auto;
    max-width: 100%;
  }

  .affair-source-link {
    margin: 0;
  }

  .affair-source-anchor {
    color: rgb(var(--v-theme-primary));
    font-weight: 600;
    overflow-wrap: anywhere;
  }
</style>
