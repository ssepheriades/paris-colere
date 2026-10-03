<template>
  <v-container class="py-8 py-md-12" max-width="840">
    <v-btn
      class="mb-6"
      prepend-icon="mdi-arrow-left"
      to="/people"
      variant="text"
    >
      Personnes
    </v-btn>

    <v-alert
      v-if="store.status === 'error'"
      :text="store.detail ?? 'La fiche est indisponible.'"
      title="Fiche indisponible"
      type="error"
      variant="tonal"
    >
      <template #append>
        <v-btn v-if="personId" color="error" variant="text" @click="store.loadOne(personId)">
          Réessayer
        </v-btn>
      </template>
    </v-alert>

    <template v-else-if="store.status === 'loading'">
      <v-skeleton-loader class="mb-8 bg-transparent" type="list-item-avatar-two-line" />
      <v-skeleton-loader class="bg-transparent" type="article" />
    </template>

    <template v-else-if="store.person">
      <header class="person-header mb-8 mb-sm-10">
        <v-avatar class="person-avatar" color="surface-light">
          <v-img v-if="store.person.photo" alt="" cover :src="store.person.photo" />
          <span v-else class="initials">{{ initials(store.person) }}</span>
        </v-avatar>

        <div>
          <div class="person-heading">
            <h1 class="page-title person-name">{{ personName(store.person) }}</h1>
            <v-btn
              v-if="wikiHref"
              class="wiki-btn"
              color="primary"
              :href="wikiHref"
              rel="noopener noreferrer"
              size="small"
              target="_blank"
              variant="outlined"
            >
              Wiki
            </v-btn>
          </div>
          <p v-if="store.person.shortDescription" class="person-summary">
            {{ store.person.shortDescription }}
          </p>
          <p v-if="store.person.bio" class="person-bio">
            {{ store.person.bio }}
          </p>
        </div>
      </header>

      <h2 class="text-overline text-medium-emphasis mb-4">Rattachements</h2>

      <v-card v-if="store.person.personLegalEntities.length === 0" class="pa-8 text-center">
        <p class="text-body-1 mb-0">Aucun rattachement.</p>
      </v-card>

      <v-timeline
        v-else
        align="start"
        class="person-timeline"
        density="comfortable"
        line-color="primary"
        side="end"
        truncate-line="both"
      >
        <v-timeline-item
          v-for="mandate in store.person.personLegalEntities"
          :key="mandate.id"
          :dot-color="mandate.endDate ? 'secondary' : 'primary'"
          :fill-dot="mandate.endDate === null"
          size="small"
        >
          <v-card class="mandate-card">
            <v-card-item>
              <v-card-title class="text-h6">
                {{ mandate.legalEntity.name }}
              </v-card-title>

              <v-card-subtitle class="mandate-dates">
                {{ formatRange(mandate) }}
              </v-card-subtitle>
            </v-card-item>

            <v-card-text class="pt-0">
              <p v-if="mandate.position" class="text-body-1 mb-3">
                {{ mandate.position }}
              </p>

              <div class="d-flex flex-wrap ga-2">
                <v-chip
                  v-if="mandate.endDate === null"
                  color="primary"
                  size="small"
                  variant="tonal"
                >
                  En cours
                </v-chip>

              </div>
            </v-card-text>
          </v-card>
        </v-timeline-item>
      </v-timeline>
    </template>
  </v-container>
</template>

<script lang="ts" setup>
  import { computed, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import { formatRange, initials, personName, usePeopleStore } from '@/stores/people'

  const route = useRoute()
  const store = usePeopleStore()

  const personId = computed(() => {
    const id = route.params.id
    return typeof id === 'string' ? id : ''
  })

  const wikiHref = computed(() => {
    const url = store.person?.wikiUrl
    if (!url) {
      return null
    }

    try {
      const parsed = new URL(url)
      if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
        return null
      }

      return parsed.href
    } catch {
      return null
    }
  })

  watch(personId, id => {
    if (id) {
      void store.loadOne(id)
    }
  }, { immediate: true })
</script>

<style scoped>
  .person-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .person-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.35rem 0.75rem;
    max-width: 100%;
    min-width: 0;
  }

  .person-name {
    flex: 0 1 auto;
    max-width: 100%;
    min-width: 0;
    font-size: clamp(1.75rem, 5vw, 2.75rem);
    line-height: 1.15;
    overflow-wrap: anywhere;
  }

  .wiki-btn {
    position: relative;
    top: -0.2em;
    letter-spacing: 0.08em;
  }

  .person-summary {
    margin: 0.45rem 0 0;
    color: #3f3a36;
    font-size: 1.05rem;
    line-height: 1.4;
  }

  .person-bio {
    margin: 0.85rem 0 0;
    max-width: 40rem;
    line-height: 1.6;
    white-space: pre-line;
  }

  @media (min-width: 600px) {
    .person-header {
      flex-direction: row;
      gap: 1.5rem;
      align-items: flex-start;
    }
  }

  .person-avatar {
    --v-avatar-height: 88px;
    border: 1px solid #e7dfd4;
    flex: 0 0 auto;
  }

  @media (min-width: 600px) {
    .person-avatar {
      --v-avatar-height: 112px;
    }
  }

  .initials {
    color: #7a1f2b;
    font-family: Fraunces, serif;
    font-size: 2rem;
    font-weight: 600;
  }

  .person-timeline {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    grid-template-columns: minmax(0, max-content) max-content minmax(0, 1fr);
  }

  .person-timeline.v-timeline--vertical.v-timeline--side-end:deep(.v-timeline-item > .v-timeline-item__body) {
    justify-self: stretch;
    min-width: 0;
    max-width: 100%;
  }

  .mandate-dates {
    opacity: 1;
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }

  .mandate-card {
    background: #fffcf8;
    max-width: 100%;
    min-width: 0;
  }

  .mandate-card :deep(.v-card-title) {
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }
</style>
