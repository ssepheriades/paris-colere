<template>
  <v-container class="py-8 py-md-12" max-width="1120">
    <header class="mb-8 mb-md-10">
      <p class="text-overline text-primary mb-2">Dossier</p>
      <h1 class="page-title text-h3">Affaires</h1>

      <p class="text-body-1 text-medium-emphasis mt-3 page-lead">
        Nom et illustration de chaque affaire.
      </p>
    </header>

    <v-alert
      v-if="store.status === 'error'"
      class="mb-6"
      :text="store.detail ?? 'La liste des affaires est indisponible.'"
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

    <div v-else-if="store.status === 'loading'" class="affair-grid">
      <v-card v-for="index in 6" :key="index">
        <v-skeleton-loader type="image, article" />
      </v-card>
    </div>

    <v-card v-else-if="store.controversies.length === 0" class="empty-card pa-8 text-center">
      <v-icon class="mb-3" color="primary" icon="mdi-folder-search-outline" size="40" />
      <p class="text-h6 page-title mb-1">Aucune affaire</p>

      <p class="text-body-2 text-medium-emphasis">
        Le dossier est vide pour le moment.
      </p>
    </v-card>

    <template v-else>
      <div class="affair-grid">
        <v-card
          v-for="controversy in store.controversies"
          :key="controversy.id"
          class="affair-card"
          :to="`/affaires/${controversy.id}`"
        >
          <v-img
            v-if="controversy.illustration"
            alt=""
            class="affair-illustration"
            cover
            height="180"
            :src="controversy.illustration"
          />

          <div v-else class="affair-placeholder">
            <v-icon color="primary" icon="mdi-image-outline" size="36" />
          </div>

          <div v-if="controversy.themes.length" class="theme-chips">
            <v-chip
              v-for="theme in controversy.themes"
              :key="theme.id"
              :color="theme.color ?? 'secondary'"
              size="small"
              :variant="theme.color ? 'flat' : 'tonal'"
            >
              {{ theme.name }}
            </v-chip>
          </div>

          <v-card-item class="pa-5">
            <v-card-title class="text-h6">
              {{ controversy.name }}
            </v-card-title>
          </v-card-item>
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
  import { useControversiesStore } from '@/stores/controversies'

  const store = useControversiesStore()
  const pageCount = computed(() => Math.max(1, Math.ceil(store.total / store.pageSize)))

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

  .affair-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
    min-width: 0;
  }

  @media (min-width: 720px) {
    .affair-grid {
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
  }

  .affair-card {
    min-width: 0;
    overflow: hidden;
  }

  .affair-card :deep(.v-card-title) {
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }

  .affair-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 180px;
    background: #f6f1ea;
    border-bottom: 1px solid #e7dfd4;
  }

  .theme-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.85rem 1.25rem 0;
  }
</style>
