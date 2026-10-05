<template>
  <v-app>
    <v-app-bar border flat>
      <v-app-bar-title class="app-title">
        <router-link to="/">Paris Colère</router-link>
      </v-app-bar-title>

      <v-btn to="/people" variant="text">
        Personnes
      </v-btn>

      <v-btn to="/affaires" variant="text">
        Affaires
      </v-btn>

      <v-btn to="/contact" variant="text">
        Contact
      </v-btn>
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
