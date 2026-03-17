<template>
  <div class="submit-page">
    <div class="submit-card">
      <h1 class="submit-title">Submit an Item</h1>
      <p class="submit-subtitle">Your submission will enter the review queue.</p>

      <!-- Success state -->
      <div v-if="submitted" class="success-box">
        <div class="success-icon">✓</div>
        <h2>Submitted!</h2>
        <p>Your item is in the queue.</p>
        <div class="success-actions">
          <button class="btn btn--primary" @click="reset">Submit another</button>
          <router-link to="/" class="btn btn--outline">View queue</router-link>
        </div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="handleSubmit" novalidate>
        <div class="field">
          <label for="title" class="label">Title <span class="required">*</span></label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            class="input"
            :class="{ 'input--error': errors.title }"
            placeholder="Short, descriptive title"
            maxlength="255"
          />
          <span v-if="errors.title" class="field-error">{{ errors.title }}</span>
        </div>

        <div class="field">
          <label for="content" class="label">Content <span class="required">*</span></label>
          <textarea
            id="content"
            v-model="form.content"
            class="input textarea"
            :class="{ 'input--error': errors.content }"
            placeholder="What would you like to report or share?"
            rows="6"
            maxlength="5000"
          />
          <div class="char-count">{{ form.content.length }} / 5000</div>
          <span v-if="errors.content" class="field-error">{{ errors.content }}</span>
        </div>

        <div v-if="serverError" class="server-error">{{ serverError }}</div>

        <button type="submit" class="btn btn--primary btn--full" :disabled="loading">
          {{ loading ? 'Submitting…' : 'Submit for review' }}
        </button>
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
  errors.title = form.title.trim() ? '' : 'Title is required.';
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
      errors.title   = errs.title?.[0] ?? '';
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
  max-width: 600px;
  margin: 0 auto;
}
.submit-card {
  background: #fff;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
}
.submit-title { font-size: 1.5rem; font-weight: 700; color: #1e1b4b; margin-bottom: 0.25rem; }
.submit-subtitle { color: #6b7280; margin-bottom: 1.75rem; font-size: 0.9rem; }

.field { margin-bottom: 1.25rem; }
.label { display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.4rem; color: #374151; }
.required { color: #dc2626; }

.input {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.55rem 0.75rem;
  font-size: 0.95rem;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.15s;
}
.input:focus { outline: 2px solid #4f46e5; border-color: transparent; }
.input--error { border-color: #dc2626; }
.textarea { resize: vertical; }

.char-count { font-size: 0.75rem; color: #9ca3af; text-align: right; margin-top: 3px; }
.field-error { font-size: 0.8rem; color: #dc2626; margin-top: 4px; display: block; }
.server-error { color: #dc2626; font-size: 0.875rem; margin-bottom: 1rem; }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.15s;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn--primary { background: #4f46e5; color: #fff; }
.btn--primary:hover:not(:disabled) { background: #4338ca; }
.btn--outline { background: #fff; color: #4f46e5; border: 1.5px solid #4f46e5; }
.btn--outline:hover { background: #eef2ff; }
.btn--full { width: 100%; }

.success-box { text-align: center; padding: 2rem 0; }
.success-icon {
  width: 56px; height: 56px;
  background: #d1fae5;
  color: #059669;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin-bottom: 1rem;
}
.success-box h2 { font-size: 1.3rem; margin-bottom: 0.4rem; color: #1e1b4b; }
.success-box p { color: #6b7280; margin-bottom: 1.5rem; }
.success-actions { display: flex; gap: 0.75rem; justify-content: center; }
</style>
