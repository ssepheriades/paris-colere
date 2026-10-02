<template>
  <v-app>
    <v-app-bar border flat>
      <v-app-bar-title class="app-title">
        <router-link to="/people">Paris Colère</router-link>
      </v-app-bar-title>
    </v-app-bar>

    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<style scoped>
  .app-title :deep(a) {
    color: inherit;
    font-family: Fraunces, serif;
    letter-spacing: -0.02em;
    text-decoration: none;
  }
</style>
