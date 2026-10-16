<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { errorMessage } from '../api/client'
import { auth } from '../stores/auth'

const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const submitting = ref(false)
const error = ref(route.query.expired ? 'Your session ended. Sign in again to continue.' : '')
const submitButton = ref(null)

// Mirrors backend/database/seeders/DatabaseSeeder.php. Shown only in development builds.
const showDemoAccounts = import.meta.env.DEV
const DEMO_PASSWORD = 'password'
const demoAccounts = [
  { name: 'Admin Lajur', email: 'admin@lajur.test', role: 'Admin', note: 'can change and delete every task' },
  { name: 'Rina Pratama', email: 'rina@lajur.test', role: 'Member' },
  { name: 'Bagus Santoso', email: 'bagus@lajur.test', role: 'Member' },
  { name: 'Dewi Kartika', email: 'dewi@lajur.test', role: 'Member' },
  { name: 'Fajar Nugroho', email: 'fajar@lajur.test', role: 'Member' },
  { name: 'Sari Wulandari', email: 'sari@lajur.test', role: 'Member' },
]

function useDemoAccount(account) {
  email.value = account.email
  password.value = DEMO_PASSWORD
  error.value = ''
  submitButton.value?.focus()
}

async function submit() {
  if (!email.value || !password.value) {
    error.value = 'Enter your email and password, or pick a demo account.'
    return
  }

  submitting.value = true
  error.value = ''
  try {
    auth.set(await api.login(email.value.trim(), password.value))
    const next = typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : '/'
    router.replace(next)
  } catch (e) {
    error.value = errorMessage(e, 'Sign in failed. Try again.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <main class="login">
      <header class="intro">
        <h1>Lajur</h1>
        <p class="muted">Shared task board for small teams.</p>
      </header>

      <div class="card">
        <form class="login-form" novalidate @submit.prevent="submit">
          <h2>Sign in</h2>

          <div class="field">
            <label for="email">Email</label>
            <input id="email" v-model="email" class="input" type="email" autocomplete="username" required />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input id="password" v-model="password" class="input" type="password" autocomplete="current-password" required />
          </div>

          <p v-if="error" class="field-error" role="alert">{{ error }}</p>

          <button ref="submitButton" class="btn btn-primary" type="submit" :disabled="submitting">
            {{ submitting ? 'Signing in' : 'Sign in' }}
          </button>
        </form>

        <section v-if="showDemoAccounts" class="demo" aria-labelledby="demo-heading">
          <h2 id="demo-heading">Demo accounts</h2>
          <p class="muted hint">Pick one to fill in the form, then sign in. Password for all: <code>password</code>.</p>

          <ul class="accounts">
            <li v-for="account in demoAccounts" :key="account.email">
              <button
                type="button"
                class="account"
                :class="{ 'is-selected': email === account.email }"
                :aria-pressed="email === account.email"
                @click="useDemoAccount(account)"
              >
                <span class="account-top">
                  <span class="name">{{ account.name }}</span>
                  <span class="role">{{ email === account.email ? 'Selected' : account.role }}</span>
                </span>
                <span class="muted email">{{ account.email }}<template v-if="account.note">, {{ account.note }}</template></span>
              </button>
            </li>
          </ul>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: var(--lane);
}

.login {
  flex: 1;
  display: grid;
  justify-items: center;
  align-content: center;
  gap: var(--space-4);
  padding: var(--space-6) var(--space-4);
  padding-top: calc(var(--space-6) + env(safe-area-inset-top, 0px));
  padding-bottom: calc(var(--space-6) + env(safe-area-inset-bottom, 0px));
}

/* Sits above the card and matches its width, so the name lines up with the card's left edge. */
.intro {
  display: grid;
  gap: var(--space-1);
  width: min(24rem, 100%);
}

.card {
  display: grid;
  gap: var(--space-6);
  width: min(24rem, 100%);
  padding: var(--space-6);
  background: var(--paper);
  border: 1px solid var(--line);
}

.login-form {
  display: grid;
  gap: var(--space-4);
}

.login-form .btn {
  width: 100%;
}

.demo {
  display: grid;
  align-content: start;
  gap: var(--space-2);
  padding-top: var(--space-4);
  border-top: 1px dashed var(--control-border);
}

/* Wide screens: form on the left, demo accounts on the right, split by a lane marking. */
@media (min-width: 760px) {
  .login:has(.demo) .intro {
    width: min(50rem, 100%);
  }

  .card:has(.demo) {
    width: min(50rem, 100%);
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 0;
  }

  .card:has(.demo) .login-form {
    align-content: start;
    padding-right: var(--space-6);
  }

  .card:has(.demo) .demo {
    padding-top: 0;
    padding-left: var(--space-6);
    border-top: 0;
    border-left: 1px dashed var(--control-border);
  }
}

.hint {
  font-size: var(--text-sm);
}

.accounts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--space-2);
}

.account {
  display: grid;
  gap: 2px;
  width: 100%;
  min-height: 2.75rem;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--line);
  background: var(--paper);
  color: var(--ink);
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.account:hover {
  border-color: var(--control-border);
  background: var(--lane);
}

.account.is-selected {
  border-color: var(--ink);
  background: var(--tint);
}

.account-top {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.name,
.role {
  font-weight: 400;
}

.role {
  font-size: var(--text-sm);
}

.email {
  font-size: var(--text-sm);
  overflow-wrap: anywhere;
}

code {
  font-family: inherit;
  font-weight: 400;
}
</style>
