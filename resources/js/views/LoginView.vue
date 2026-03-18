<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-logo">🛡️</div>
      <h1 class="login-title">Review Queue</h1>
      <p class="login-sub">{{ isRegister ? 'Create an account to get started' : 'Sign in to access the moderation queue' }}</p>

      <form class="login-form" @submit.prevent="submit">

        <div v-if="isRegister" class="field">
          <label class="field-label">Name</label>
          <input
            v-model="name"
            type="text"
            class="field-input"
            placeholder="Your name"
            autocomplete="name"
            required
          />
        </div>

        <div class="field">
          <label class="field-label">Email</label>
          <input
            v-model="email"
            type="email"
            class="field-input"
            placeholder="you@example.com"
            autocomplete="email"
            required
          />
        </div>

        <div class="field">
          <label class="field-label">Password</label>
          <input
            v-model="password"
            type="password"
            class="field-input"
            :placeholder="isRegister ? 'At least 8 characters' : '••••••••'"
            autocomplete="current-password"
            required
          />
        </div>

        <Transition name="err">
          <div v-if="error" class="login-error">⚠️ {{ error }}</div>
        </Transition>

        <button class="login-btn" :disabled="loading" type="submit">
          <span v-if="loading" class="spinner"></span>
          {{ loading ? (isRegister ? 'Creating account…' : 'Signing in…') : (isRegister ? 'Create account' : 'Sign in') }}
        </button>
      </form>

      <p v-if="!isRegister" class="login-hint">Default: <code>test@example.com</code> / <code>password</code></p>

      <div class="login-toggle">
        {{ isRegister ? 'Already have an account?' : "Don't have an account?" }}
        <button class="toggle-btn" @click="switchMode">
          {{ isRegister ? 'Sign in' : 'Sign up' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { register as apiRegister } from '@/api/auth';

const auth       = useAuthStore();
const router     = useRouter();
const isRegister = ref(false);
const name       = ref('');
const email      = ref('');
const password   = ref('');
const loading    = ref(false);
const error      = ref('');

function switchMode() {
  isRegister.value = !isRegister.value;
  error.value      = '';
}

async function submit() {
  loading.value = true;
  error.value   = '';
  try {
    if (isRegister.value) {
      const user = await apiRegister(name.value, email.value, password.value);
      auth.user    = user;
      auth.checked = true;
    } else {
      await auth.login(email.value, password.value);
    }
    router.push('/');
  } catch (e) {
    const data = e.response?.data;
    error.value =
      data?.errors?.email?.[0] ??
      data?.errors?.password?.[0] ??
      data?.errors?.name?.[0] ??
      data?.message ??
      'Something went wrong.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  padding: 1.5rem;
}

.login-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 400px;
  box-shadow: var(--shadow-md);
  text-align: center;
}

.login-logo { font-size: 2.5rem; margin-bottom: 0.75rem; }

.login-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.35rem;
}

.login-sub {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin-bottom: 1.75rem;
}

.login-form { text-align: left; }

.field { margin-bottom: 1.1rem; }

.field-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}

.field-input {
  width: 100%;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 0.65rem 0.875rem;
  font-size: 0.925rem;
  font-family: inherit;
  background: var(--bg-input);
  color: var(--text);
  box-sizing: border-box;
  transition: all 0.2s;
}
.field-input:focus {
  outline: none;
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}

.login-error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.login-btn {
  width: 100%;
  padding: 0.75rem;
  background: var(--primary-grad);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: opacity 0.2s;
  margin-top: 0.5rem;
}
.login-btn:disabled { opacity: 0.65; cursor: not-allowed; }
.login-btn:hover:not(:disabled) { opacity: 0.9; }

.login-hint {
  margin-top: 1.25rem;
  font-size: 0.78rem;
  color: var(--text-faint);
  margin-bottom: 0;
}
.login-hint code {
  background: var(--bg-input);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 1px 5px;
  font-size: 0.78rem;
  color: var(--text-muted);
}

.login-toggle {
  margin-top: 1.5rem;
  font-size: 0.85rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.toggle-btn {
  background: none;
  border: none;
  color: var(--primary);
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.toggle-btn:hover { opacity: 0.75; }

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.err-enter-active { transition: all 0.2s; }
.err-leave-active { transition: all 0.15s; }
.err-enter-from, .err-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
