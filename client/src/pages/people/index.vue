<template>
  <v-container class="py-8 py-md-12" max-width="1120">
    <header class="mb-8 mb-md-10">
      <p class="text-overline text-primary mb-2">Annuaire</p>
      <h1 class="page-title">Personnes</h1>

      <p class="text-body-1 text-medium-emphasis mt-3 page-lead">
        Photo, nom, et le mandat en cours.
      </p>
    </header>

    <v-alert
      v-if="store.status === 'error'"
      class="mb-6"
      :text="store.detail ?? 'La liste des personnes est indisponible.'"
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

    <div v-else-if="store.status === 'loading'" class="person-grid">
      <v-card v-for="index in 6" :key="index">
        <v-skeleton-loader type="list-item-avatar-two-line" />
      </v-card>
    </div>

    <v-card v-else-if="store.people.length === 0" class="empty-card pa-8 text-center">
      <v-icon class="mb-3" color="primary" icon="mdi-account-search-outline" size="40" />
      <p class="text-h6 page-title mb-1">Aucune personne</p>

      <p class="text-body-2 text-medium-emphasis">
        L’annuaire est vide pour le moment.
      </p>
    </v-card>

    <template v-else>
      <div class="person-grid">
        <v-card
          v-for="person in store.people"
          :key="person.id"
          class="person-card"
          :to="`/people/${person.id}`"
        >
          <v-card-item class="pa-5">
            <template #prepend>
              <v-avatar class="person-avatar" color="surface-light" size="76">
                <v-img v-if="person.photo" alt="" cover :src="person.photo" />
                <span v-else class="initials">{{ initials(person) }}</span>
              </v-avatar>
            </template>

            <v-card-title class="text-h6">
              {{ personName(person) }}
            </v-card-title>

            <v-card-subtitle class="mandate-list">
              <template v-if="currentMandates(person).length === 0">
                <span class="text-medium-emphasis">Aucun mandat en cours</span>
              </template>

              <span
                v-for="mandate in currentMandates(person)"
                :key="mandate.id"
                class="mandate"
              >
                <span v-if="mandate.position" class="mandate-position">{{ mandate.position }}</span>
                <span class="mandate-entity">{{ mandate.legalEntity.name }}</span>
              </span>
            </v-card-subtitle>
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
  import { currentMandates, initials, personName, usePeopleStore } from '@/stores/people'

  const store = usePeopleStore()
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
    font-size: 2rem;
    line-height: 1.15;
  }

  @media (min-width: 600px) {
    .page-title {
      font-size: 2.75rem;
    }
  }

  .person-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
    min-width: 0;
  }

  @media (min-width: 720px) {
    .person-grid {
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
  }

  .person-card {
    min-width: 0;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
  }

  .person-card :deep(.v-card-item) {
    grid-template-columns: max-content minmax(0, 1fr) max-content;
  }

  .person-card :deep(.v-card-title),
  .person-card :deep(.v-card-subtitle) {
    overflow: visible;
    text-overflow: unset;
    white-space: normal;
  }

  .person-card:hover {
    box-shadow: 0 14px 32px rgb(28 25 23 / 8%);
    transform: translateY(-2px);
  }

  .person-avatar {
    border: 1px solid #e7dfd4;
  }

  .initials {
    color: #7a1f2b;
    font-family: Fraunces, serif;
    font-size: 1.35rem;
    font-weight: 600;
  }

  .mandate-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    opacity: 1;
    padding-top: 0.35rem;
    white-space: normal;
  }

  .mandate {
    display: flex;
    flex-direction: column;
  }

  .mandate-position {
    color: #7a1f2b;
    font-weight: 600;
  }

  .mandate-entity {
    color: #3f3a36;
  }
</style>
