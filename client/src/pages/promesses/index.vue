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
      <div v-if="statusFilters.length" class="status-filters">
        <v-chip
          color="primary"
          size="small"
          :variant="selectedStatus === null ? 'flat' : 'tonal'"
          @click="clearFilters"
        >
          Tous
        </v-chip>
        <v-chip
          v-for="filter in statusFilters"
          :key="filter.value"
          color="primary"
          :prepend-icon="STATUS_ICONS[filter.value]"
          size="small"
          :variant="selectedStatus === filter.value ? 'flat' : 'tonal'"
          @click="toggleStatus(filter.value)"
        >
          {{ filter.label }}
        </v-chip>
      </div>

      <div v-if="themeFilters.length" class="status-filters">
        <v-chip
          v-for="theme in themeFilters"
          :key="theme.id"
          :color="theme.color ?? 'secondary'"
          :prepend-icon="theme.icon || undefined"
          size="small"
          :variant="selectedThemeId === theme.id ? 'flat' : 'tonal'"
          @click="toggleTheme(theme.id)"
        >
          {{ theme.name }}
        </v-chip>
      </div>

      <v-card v-if="visiblePromises.length === 0" class="empty-card pa-8 text-center" rounded="xl">
        <v-icon class="mb-3" color="primary" icon="mdi-filter-off-outline" size="40" />
        <p class="text-h6 page-title mb-1">Aucune promesse</p>

        <p class="text-body-2 text-medium-emphasis">
          Rien ne correspond à ces filtres.
        </p>
      </v-card>

      <div v-else class="promise-list">
        <v-card
          v-for="promise in visiblePromises"
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

              <div v-if="promise.result" class="promise-result">
                <p class="promise-result-value">
                  {{ resultText(promise.result) }}
                </p>
                <v-progress-linear
                  v-if="resultProgress(promise.result) !== null"
                  color="primary"
                  height="6"
                  :model-value="resultProgress(promise.result) ?? 0"
                  rounded
                />
              </div>

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
  import { computed, onMounted, ref } from 'vue'
  import { STATUS_ICONS, STATUS_LABELS, usePromisesStore, type CampaignPromiseStatus, type PromiseResult, type Theme } from '@/stores/promises'

  const store = usePromisesStore()
  const pageCount = computed(() => Math.max(1, Math.ceil(store.total / store.pageSize)))
  const selectedStatus = ref<CampaignPromiseStatus | null>(null)
  const selectedThemeId = ref<string | null>(null)

  const statusOrder = Object.keys(STATUS_LABELS) as CampaignPromiseStatus[]

  const statusFilters = computed(() => {
    const present = new Set(store.promises.map(promise => promise.status))

    return statusOrder
      .filter(status => present.has(status))
      .map(value => ({
        value,
        label: STATUS_LABELS[value],
      }))
  })

  const themeFilters = computed(() => {
    const themes = new Map<string, Theme>()

    for (const promise of store.promises) {
      for (const theme of promise.themes) {
        themes.set(theme.id, theme)
      }
    }

    return [...themes.values()].sort((left, right) => left.name.localeCompare(right.name, 'fr'))
  })

  const visiblePromises = computed(() => store.promises.filter(promise => {
    if (selectedStatus.value !== null && promise.status !== selectedStatus.value) {
      return false
    }

    if (selectedThemeId.value !== null && !promise.themes.some(theme => theme.id === selectedThemeId.value)) {
      return false
    }

    return true
  }))

  function toggleStatus (status: CampaignPromiseStatus) {
    selectedStatus.value = selectedStatus.value === status ? null : status
  }

  function toggleTheme (themeId: string) {
    selectedThemeId.value = selectedThemeId.value === themeId ? null : themeId
  }

  function clearFilters () {
    selectedStatus.value = null
    selectedThemeId.value = null
  }

  const numberFormat = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 })

  function resultText (result: PromiseResult): string {
    if (result.resultType === 'percentage') {
      return `${numberFormat.format(result.resultValue)} %`
    }

    const objective = result.objective === null ? '' : ` / ${numberFormat.format(result.objective)}`
    const label = result.resultLabel ? ` ${result.resultLabel}` : ''

    return `${numberFormat.format(result.resultValue)}${objective}${label}`
  }

  function resultProgress (result: PromiseResult): number | null {
    if (result.resultType === 'percentage') {
      return Math.min(100, Math.max(0, result.resultValue))
    }

    if (result.objective === null || result.objective <= 0) {
      return null
    }

    return Math.min(100, Math.max(0, (result.resultValue / result.objective) * 100))
  }

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

  .status-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
  }

  .status-filters :deep(.v-chip) {
    cursor: pointer;
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

  .promise-result {
    margin-top: 1rem;
  }

  .promise-result-value {
    margin: 0 0 0.4rem;
    font-weight: 600;
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
