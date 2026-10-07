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

      <v-btn
        class="mt-2"
        color="primary"
        :to="`/contact?affaire=${store.controversy.id}`"
        variant="outlined"
      >
        Contacter à propos de cette affaire
      </v-btn>
    </header>
  </v-container>
</template>

<script lang="ts" setup>
  import { computed, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import { useControversiesStore } from '@/stores/controversies'

  const route = useRoute()
  const store = useControversiesStore()

  const controversyId = computed(() => {
    const id = route.params.id
    return typeof id === 'string' ? id : ''
  })

  watch(controversyId, id => {
    if (id) {
      void store.loadOne(id)
    }
  }, { immediate: true })

  function figureParts (figure: string) {
    return figure.split(/([+\-±−\d]+)/).filter(Boolean).map(text => ({
      text,
      accent: /^[+\-±−\d]+$/.test(text),
    }))
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
</style>
