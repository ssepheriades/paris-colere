/**
 * router/index.ts
 *
 * Manual routes for ./src/pages/*.vue
 */

// Composables
import { createRouter, createWebHistory } from 'vue-router'
import AffairesIndex from '@/pages/affaires/index.vue'
import AffairesShow from '@/pages/affaires/show.vue'
import Contact from '@/pages/contact.vue'
import Home from '@/pages/home.vue'
import PeopleIndex from '@/pages/people/index.vue'
import PeopleShow from '@/pages/people/show.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: Home,
    },
    {
      path: '/people',
      component: PeopleIndex,
    },
    {
      path: '/people/:id',
      component: PeopleShow,
    },
    {
      path: '/affaires',
      component: AffairesIndex,
    },
    {
      path: '/affaires/:id',
      component: AffairesShow,
    },
    {
      path: '/contact',
      component: Contact,
    },
  ],
})

export default router
