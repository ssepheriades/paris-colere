/**
 * plugins/vuetify.ts
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Composables
import { createVuetify } from 'vuetify'
// Styles
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

// https://vuetifyjs.com/en/introduction/why-vuetify/#feature-guides
export default createVuetify({
  theme: {
    defaultTheme: 'pierre',
    themes: {
      pierre: {
        dark: false,
        colors: {
          'background': '#F6F1EA',
          'surface': '#FFFCF8',
          'surface-bright': '#FFFFFF',
          'surface-light': '#F3EDE4',
          'surface-variant': '#E7DFD4',
          'primary': '#7A1F2B',
          'secondary': '#3F3A36',
          'on-background': '#1C1917',
          'on-surface': '#1C1917',
          'on-primary': '#FFFCF8',
          'on-secondary': '#FFFCF8',
        },
      },
    },
  },
  defaults: {
    VCard: {
      border: true,
      elevation: 0,
      rounded: 'lg',
    },
  },
})
