<template>
  <div class="submit-page">
    <div class="submit-hero">
      <div class="hero-icon">📝</div>
      <h1 class="hero-title">Submit an Item</h1>
      <p class="hero-sub">Your submission enters the review queue instantly.</p>
    </div>

    <div class="submit-card">
      <!-- Success state -->
      <Transition name="success">
        <div v-if="submitted" class="success-box">
          <div class="success-ring">
            <div class="success-check">✓</div>
          </div>
          <h2 class="success-title">Submitted!</h2>
          <p class="success-msg">Your item is now in the review queue.</p>
          <div class="success-actions">
            <button class="btn btn--primary" @click="reset">Submit another</button>
            <router-link to="/" class="btn btn--outline">View queue →</router-link>
          </div>
        </div>
      </Transition>

      <!-- Form -->
      <form v-if="!submitted" @submit.prevent="handleSubmit" novalidate>
        <div class="field">
          <label for="title" class="label">
            Title <span class="required">*</span>
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            class="input"
            :class="{ 'input--error': errors.title }"
            placeholder="Short, descriptive title"
            maxlength="255"
            autocomplete="off"
          />
          <Transition name="err">
            <span v-if="errors.title" class="field-error">⚠ {{ errors.title }}</span>
          </Transition>
        </div>

        <div class="field">
          <label for="content" class="label">
            Content <span class="required">*</span>
          </label>
          <textarea
            id="content"
            v-model="form.content"
            class="input textarea"
            :class="{ 'input--error': errors.content }"
            placeholder="What would you like to report or share?"
            rows="6"
            maxlength="5000"
          />
          <div class="field-footer">
            <Transition name="err">
              <span v-if="errors.content" class="field-error">⚠ {{ errors.content }}</span>
            </Transition>
            <span class="char-count" :class="{ 'char-count--near': form.content.length > 4500 }">
              {{ form.content.length }} / 5000
            </span>
          </div>
        </div>

        <Transition name="err">
          <div v-if="serverError" class="server-error">{{ serverError }}</div>
        </Transition>

        <button type="submit" class="btn btn--primary btn--full" :disabled="loading">
          <span v-if="loading" class="btn-spinner"></span>
          {{ loading ? 'Submitting…' : 'Submit for review →' }}
        </button>
        <div class="form-footer">
          <router-link to="/" class="back-link">← Back to queue</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { submitItem } from '@/api/items';

const form = reactive({ title: '', content: '' });
const errors = reactive({ title: '', content: '' });
const loading = ref(false);
const submitted = ref(false);
const serverError = ref('');

function validate() {
  errors.title   = form.title.trim()   ? '' : 'Title is required.';
  errors.content = form.content.trim() ? '' : 'Content is required.';
  return !errors.title && !errors.content;
}

async function handleSubmit() {
  if (!validate()) return;
  loading.value = true;
  serverError.value = '';
  try {
    await submitItem({ title: form.title.trim(), content: form.content.trim() });
    submitted.value = true;
  } catch (e) {
    if (e.response?.status === 422) {
      const errs = e.response.data.errors ?? {};
      errors.title   = errs.title?.[0]   ?? '';
      errors.content = errs.content?.[0] ?? '';
    } else {
      serverError.value = 'Something went wrong. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}

function reset() {
  form.title = '';
  form.content = '';
  errors.title = '';
  errors.content = '';
  serverError.value = '';
  submitted.value = false;
}
</script>

<style scoped>
.submit-page {
  max-width: 580px;
  margin: 0 auto;
}

.submit-hero {
  text-align: center;
  margin-bottom: 2rem;
}
.form-footer {
  text-align: center;
  margin-top: 1rem;
}
.back-link {
  color: var(--text-muted);
  font-size: 0.85rem;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.15s;
}
.back-link:hover { color: var(--primary); }
.hero-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
.hero-title {
  font-size: 2rem;
  font-weight: 800;
  background: var(--primary-grad);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.03em;
  margin-bottom: 0.3rem;
}
.hero-sub { color: var(--text-faint); font-size: 0.9rem; }

.submit-card {
  background: var(--bg-card);
  border-radius: 18px;
  padding: 2rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border);
  min-height: 200px;
}

.field { margin-bottom: 1.4rem; }
.label {
  display: block;
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
  color: var(--text);
}
.required { color: #ef4444; }

.input {
  width: 100%;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 0.65rem 0.875rem;
  font-size: 0.95rem;
  font-family: inherit;
  box-sizing: border-box;
  background: var(--bg-input);
  color: var(--text);
  transition: all 0.2s;
}
.input:focus {
  outline: none;
  border-color: var(--border-focus);
  background: var(--bg-card);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.input--error { border-color: #ef4444 !important; background: #fff5f5 !important; }
.textarea { resize: vertical; min-height: 130px; }

.field-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 4px;
}
.field-error { font-size: 0.8rem; color: #dc2626; }
.char-count { font-size: 0.75rem; color: var(--text-faint); margin-left: auto; }
.char-count--near { color: #f59e0b; font-weight: 600; }

.server-error {
  background: #fff5f5;
  border: 1px solid #fca5a5;
  color: #dc2626;
  font-size: 0.875rem;
  padding: 0.6rem 0.875rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.7rem 1.75rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn--primary {
  background: var(--primary-grad);
  color: #fff;
  box-shadow: var(--shadow-md);
}
.btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.08);
}
.btn--outline {
  background: var(--bg-card);
  color: var(--primary);
  border: 2px solid var(--border-focus);
}
.btn--outline:hover { background: var(--bg-hover); }
.btn--full { width: 100%; }

.btn-spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Success */
.success-box {
  text-align: center;
  padding: 1.5rem 0;
}
.success-ring {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  border: 2px solid #6ee7b7;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.1rem;
  box-shadow: 0 0 0 6px #ecfdf5;
  animation: pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.success-check { font-size: 1.9rem; color: #059669; }
.success-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.4rem;
}
.success-msg { color: var(--text-muted); margin-bottom: 1.5rem; }
.success-actions { display: flex; gap: 0.75rem; justify-content: center; }

/* Transitions */
.success-enter-active { animation: pop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.err-enter-active { transition: all 0.2s; }
.err-leave-active { transition: all 0.15s; }
.err-enter-from, .err-leave-to { opacity: 0; transform: translateY(-4px); }

@keyframes pop {
  from { opacity: 0; transform: scale(0.8); }
  to   { opacity: 1; transform: scale(1); }
}
</style>
