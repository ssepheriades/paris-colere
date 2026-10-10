<template>
  <v-app>
    <v-app-bar border flat>
      <v-app-bar-title class="app-title">
        <router-link to="/">Paris Colère</router-link>
      </v-app-bar-title>

      <v-btn to="/affaires" variant="text">
        Affaires
      </v-btn>

      <v-btn variant="text" @click="contact.open()">
        Contact
      </v-btn>
    </v-app-bar>

    <v-main>
      <router-view />
    </v-main>

    <ContactSheet />
  </v-app>
</template>

<script lang="ts" setup>
  import ContactSheet from '@/components/ContactSheet.vue'
  import { useContactStore } from '@/stores/contact'

  const contact = useContactStore()
</script>

<style scoped>
  .app-title :deep(a) {
    color: inherit;
    font-family: Fraunces, serif;
    letter-spacing: -0.02em;
    text-decoration: none;
  }
</style>
