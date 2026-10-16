import { createRouter, createWebHistory } from 'vue-router'
import { auth } from './stores/auth'

// Each screen is its own chunk: the sign-in page never downloads the board's code.
const LoginView = () => import('./views/LoginView.vue')
const BoardView = () => import('./views/BoardView.vue')

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
    { path: '/', name: 'board', component: BoardView },
    // Same board, with one task's panel open. Gives every task a link you can share.
    { path: '/tasks/:id(\\d+)', name: 'task', component: BoardView, props: true },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  if (!to.meta.guest && !auth.isSignedIn) {
    return { name: 'login', query: to.fullPath !== '/' ? { next: to.fullPath } : {} }
  }
  if (to.meta.guest && auth.isSignedIn) {
    return { name: 'board' }
  }
})
