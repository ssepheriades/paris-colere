<template>
  <v-container class="py-8 py-md-12" max-width="860">
    <header class="mb-8 mb-md-10">
      <p class="text-overline text-primary mb-2">Mandat</p>
      <h1 class="page-title text-h3">Promesses</h1>

      <p class="text-body-1 text-medium-emphasis mt-3 page-lead">
        Ce qui a été dit, et ce que les articles permettent d’en vérifier.
      </p>
    </header>

    <v-alert
      v-if="store.status === 'error'"
      class="mb-6"
      :text="store.detail ?? 'La liste des promesses est indisponible.'"
      title="Liste indisponible"
      type="error"
      variant="tonal"
    >
      <template #append>
        <v-btn color="error" variant="text" @click="store.loadPage(store.page)">
          Réessayer
        </v-btn>
      </template>
    </v-alert>

    <div v-else-if="store.status === 'loading'" class="promise-list">
      <v-card v-for="index in 4" :key="index" rounded="xl">
        <v-skeleton-loader type="article" />
      </v-card>
    </div>

    <v-card v-else-if="store.promises.length === 0" class="empty-card pa-8 text-center" rounded="xl">
      <v-icon class="mb-3" color="primary" icon="mdi-text-box-search-outline" size="40" />
      <p class="text-h6 page-title mb-1">Aucune promesse</p>

      <p class="text-body-2 text-medium-emphasis">
        Le suivi est vide pour le moment.
      </p>
    </v-card>

    <template v-else>
      <div class="promise-list">
        <v-card
          v-for="promise in store.promises"
          :key="promise.id"
          class="promise-card"
          rounded="xl"
        >
          <v-img
            v-if="promise.illustration"
            alt=""
            class="promise-illustration"
            cover
            height="180"
            :src="promise.illustration"
          />

          <div class="promise-layout">
            <p class="status-mark" :data-status="promise.status">
              <v-icon class="status-icon" :icon="STATUS_ICONS[promise.status]" size="20" />
              {{ STATUS_LABELS[promise.status] }}
            </p>

            <div class="promise-body">
              <div v-if="promise.themes.length" class="theme-chips">
                <v-chip
                  v-for="theme in promise.themes"
                  :key="theme.id"
                  :color="theme.color ?? 'secondary'"
                  :prepend-icon="theme.icon || undefined"
                  size="small"
                  :variant="theme.color ? 'flat' : 'tonal'"
                >
                  {{ theme.name }}
                </v-chip>
              </div>

              <h2 class="text-h6 promise-title">
                {{ promise.title }}
              </h2>

              <blockquote class="promise-quote">
                {{ promise.quote }}
              </blockquote>

              <ul v-if="promise.sources.length" class="source-list">
                <li v-for="source in promise.sources" :key="source.id">
                  <a
                    v-if="httpUrl(source.url)"
                    class="source-link"
                    :href="httpUrl(source.url) ?? undefined"
                    rel="noopener noreferrer"
                    target="_blank"
                  >
                    {{ source.title || source.url }}
                  </a>
                  <span v-else>{{ source.title || source.url }}</span>
                </li>
              </ul>
            </div>
          </div>
        </v-card>
      </div>

      <div v-if="pageCount > 1" class="d-flex justify-center mt-8">
        <v-pagination
          :length="pageCount"
          :model-value="store.page"
          @update:model-value="store.loadPage"
        />
      </div>
    </template>
  </v-container>
</template>

<script lang="ts" setup>
  import { computed, onMounted } from 'vue'
  import { STATUS_ICONS, STATUS_LABELS, usePromisesStore } from '@/stores/promises'

  const store = usePromisesStore()
  const pageCount = computed(() => Math.max(1, Math.ceil(store.total / store.pageSize)))

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

  onMounted(() => {
    void store.loadPage(1)
  })
</script>

<style scoped>
  .page-lead {
    max-width: 36rem;
  }

  .page-title {
    font-size: clamp(2rem, 4vw, 2.75rem);
    line-height: 1.15;
  }

  .promise-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .promise-card {
    overflow: hidden;
  }

  .promise-illustration {
    border-bottom: 1px solid #e7dfd4;
  }

  .promise-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
  }

  @media (min-width: 720px) {
    .promise-layout {
      grid-template-columns: 12.5rem minmax(0, 1fr);
    }
  }

  .status-mark {
    display: flex;
    align-items: center;
    margin: 0;
    padding: 1rem 1.25rem;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    gap: 0.45rem;
    border-bottom: 1px solid #e7dfd4;
  }

  .status-icon {
    flex: none;
  }

  @media (min-width: 720px) {
    .status-mark {
      border-bottom: 0;
      border-right: 1px solid #e7dfd4;
    }
  }

  .status-mark[data-status="kept"] {
    color: #145c3e;
    background: #e7f3ec;
  }

  .status-mark[data-status="partial"] {
    color: #6e4510;
    background: #f8efd8;
  }

  .status-mark[data-status="broken"] {
    color: #7a1f2b;
    background: #f8e8ea;
  }

  .status-mark[data-status="in_progress"] {
    color: #3f3a36;
    background: #f3ede4;
  }

  .promise-body {
    min-width: 0;
    padding: 1.15rem 1.25rem 1.25rem;
  }

  .theme-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .promise-title {
    font-family: Fraunces, serif;
    letter-spacing: -0.02em;
    line-height: 1.3;
  }

  .promise-quote {
    margin: 0.75rem 0 0;
    padding-left: 0.9rem;
    border-left: 2px solid #7a1f2b;
    color: #3f3a36;
    font-family: Fraunces, serif;
    font-size: 1.05rem;
    font-style: italic;
    line-height: 1.45;
  }

  .source-list {
    margin: 1rem 0 0;
    padding: 0;
    list-style: none;
  }

  .source-list li + li {
    margin-top: 0.35rem;
  }

  .source-link {
    color: #7a1f2b;
    text-underline-offset: 0.18em;
  }
</style>
