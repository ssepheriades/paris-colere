<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="submit">
    <v-alert
      v-if="success"
      class="mb-6"
      text="Votre message a bien été envoyé. Merci."
      title="Message envoyé"
      type="success"
      variant="tonal"
    />

    <v-alert
      v-else-if="error"
      class="mb-6"
      :text="error"
      title="Envoi impossible"
      type="error"
      variant="tonal"
    />

    <div v-if="!success" class="form-fields">
      <v-text-field
        v-model="name"
        label="Nom"
        :rules="[required]"
        variant="outlined"
      />

      <v-text-field
        v-model="email"
        label="Email"
        type="email"
        :rules="[required, emailRule]"
        variant="outlined"
      />

      <v-select
        v-model="controversyId"
        clearable
        :items="affairItems"
        item-title="name"
        item-value="id"
        label="Affaire (optionnel)"
        :loading="affairsLoading"
        variant="outlined"
      />

      <v-textarea
        v-model="message"
        label="Message"
        :rules="[required]"
        rows="5"
        variant="outlined"
      />

      <v-btn
        color="primary"
        :loading="submitting"
        size="large"
        type="submit"
        variant="flat"
      >
        Envoyer
      </v-btn>
    </div>
  </v-form>
</template>

<script lang="ts" setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import { useControversiesStore } from '@/stores/controversies'

  const props = defineProps<{
    affaire?: string | null
  }>()

  const store = useControversiesStore()

  const formRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
  const valid = ref(false)
  const name = ref('')
  const email = ref('')
  const message = ref('')
  const controversyId = ref<string | null>(null)
  const submitting = ref(false)
  const success = ref(false)
  const error = ref<string | null>(null)
  const affairsLoading = ref(false)

  const required = (value: unknown) =>
    (typeof value === 'string' && value.trim() !== '') || 'Champ requis'

  const emailRule = (value: unknown) =>
    (typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
    || 'Email invalide'

  const affairItems = computed(() => store.controversies)

  function resetFeedback () {
    success.value = false
    error.value = null
  }

  function applyPrefill (id: string | null | undefined) {
    controversyId.value = id ?? null
  }

  watch(() => props.affaire, id => {
    resetFeedback()
    applyPrefill(id)
  }, { immediate: true })

  onMounted(async () => {
    affairsLoading.value = true
    try {
      if (store.controversies.length === 0) {
        await store.loadPage(1)
      }
      applyPrefill(props.affaire)
    } finally {
      affairsLoading.value = false
    }
  })

  async function submit () {
    error.value = null
    success.value = false

    const result = await formRef.value?.validate()
    if (!result?.valid) {
      return
    }

    submitting.value = true

    try {
      const body: Record<string, string> = {
        name: name.value.trim(),
        email: email.value.trim(),
        message: message.value.trim(),
      }

      if (controversyId.value) {
        body.controversy = `/api/controversies/${controversyId.value}`
      }

      const response = await fetch('/api/contacts', {
        method: 'POST',
        headers: {
          Accept: 'application/ld+json',
          'Content-Type': 'application/ld+json',
        },
        body: JSON.stringify(body),
      })

      if (!response.ok) {
        error.value = 'Impossible d’envoyer le message. Vérifiez les champs et réessayez.'
        return
      }

      success.value = true
      name.value = ''
      email.value = ''
      message.value = ''
    } catch {
      error.value = 'Connexion impossible. Réessayez plus tard.'
    } finally {
      submitting.value = false
    }
  }
</script>

<style scoped>
  .form-fields {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
</style>
