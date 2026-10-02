/**
 * router/index.ts
 *
 * Manual routes for ./src/pages/*.vue
 */

// Composables
import { createRouter, createWebHistory } from 'vue-router'
import PeopleIndex from '@/pages/people/index.vue'
import PeopleShow from '@/pages/people/show.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/people',
    },
    {
      path: '/people',
      component: PeopleIndex,
    },
    {
      path: '/people/:id',
      component: PeopleShow,
    },
  ],
})

export default router
