<template>
  <v-container class="py-8 py-md-12" max-width="640">
    <header class="mb-8 mb-md-10">
      <p class="text-overline text-primary mb-2">
        Écrire
      </p>
      <h1 class="page-title text-h3">
        Contact
      </h1>

      <p class="text-body-1 text-medium-emphasis mt-3 page-lead">
        Une info, une précision, un signalement — laissez-nous un message.
      </p>
    </header>

    <ContactForm :affaire="affaireId" />
  </v-container>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import { useRoute } from 'vue-router'
  import ContactForm from '@/components/ContactForm.vue'

  const route = useRoute()

  const affaireId = computed(() => {
    const value = route.query.affaire
    return typeof value === 'string' && value !== '' ? value : null
  })
</script>

<style scoped>
  .page-title {
    font-family: Fraunces, serif;
    letter-spacing: -0.02em;
  }

  .page-lead {
    max-width: 36rem;
  }
</style>
