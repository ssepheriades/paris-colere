import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useContactStore = defineStore('contact', () => {
  const isOpen = ref(false)
  const affaireId = ref<string | null>(null)

  function open (nextAffaireId: string | null = null) {
    affaireId.value = nextAffaireId
    isOpen.value = true
  }

  function close () {
    isOpen.value = false
    affaireId.value = null
  }

  return { isOpen, affaireId, open, close }
})
