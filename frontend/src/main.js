import { createApp } from 'vue'
// Latin subset only (covers U+0000-00FF); the full import ships Cyrillic, Greek and Vietnamese files too.
import '@fontsource/ibm-plex-sans/latin-300.css'
import '@fontsource/ibm-plex-sans/latin-400.css'
import './styles.css'
import App from './App.vue'
import { router } from './router'
import { auth } from './stores/auth'
import { onUnauthorized } from './api/client'

auth.load()

onUnauthorized(() => {
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { expired: '1' } })
  }
})

createApp(App).use(router).mount('#app')
