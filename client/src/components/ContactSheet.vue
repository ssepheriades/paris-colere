<template>
  <v-bottom-sheet
    :model-value="contact.isOpen"
    scrim
    @update:model-value="onSheetUpdate"
  >
    <v-card class="contact-sheet-card">
      <v-card-title class="contact-sheet-title d-flex align-center justify-space-between">
        <span>Contact</span>
        <v-btn
          aria-label="Fermer"
          icon="mdi-close"
          variant="text"
          @click="contact.close()"
        />
      </v-card-title>

      <v-card-text>
        <p class="contact-sheet-lead text-body-2 text-medium-emphasis mb-6">
          Une info, une précision, un signalement — laissez-nous un message.
        </p>

        <ContactForm
          v-if="contact.isOpen"
          :affaire="contact.affaireId"
        />
      </v-card-text>
    </v-card>
  </v-bottom-sheet>
</template>

<script lang="ts" setup>
  import ContactForm from '@/components/ContactForm.vue'
  import { useContactStore } from '@/stores/contact'

  const contact = useContactStore()

  function onSheetUpdate (open: boolean) {
    if (!open) {
      contact.close()
    }
  }
</script>

<style scoped>
  .contact-sheet-card {
    max-width: 640px;
    margin-inline: auto;
  }

  .contact-sheet-title {
    font-family: Fraunces, serif;
    letter-spacing: -0.02em;
  }

  .contact-sheet-lead {
    max-width: 36rem;
  }
</style>
