<template>
  <div class="home">
    <h1>Welcome to Vue + Laravel</h1>
    <p class="subtitle">A boilerplate with Vue 3 frontend and Laravel 12 API backend.</p>

    <div class="card">
      <h2>Pinia Counter</h2>
      <p>Count: <strong>{{ count }}</strong> (double: {{ doubleCount }})</p>
      <div class="button-group">
        <button @click="decrement" class="btn btn-secondary">-</button>
        <button @click="reset" class="btn btn-warning">Reset</button>
        <button @click="increment" class="btn btn-primary">+</button>
      </div>
    </div>

    <div class="card">
      <h2>API Example</h2>
      <button @click="fetchMessage" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Loading...' : 'Fetch from Laravel API' }}
      </button>
      <p v-if="message" class="api-result">{{ message }}</p>
      <p v-if="error" class="api-error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useCounterStore } from '../stores/counter';
import axios from 'axios';

const store = useCounterStore();
const { count, doubleCount } = storeToRefs(store);
const { increment, decrement, reset } = store;

const message = ref('');
const error = ref('');
const loading = ref(false);

async function fetchMessage() {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get('/api/hello');
        message.value = response.data.message;
    } catch (err) {
        error.value = 'Failed to fetch from API. Make sure the Laravel server is running.';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.home {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

h1 {
  font-size: 2rem;
  color: #4f46e5;
}

.subtitle {
  color: #666;
  margin-top: -1rem;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card h2 {
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.button-group {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn {
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: opacity 0.2s;
}

.btn:hover { opacity: 0.85; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-primary { background-color: #4f46e5; color: white; }
.btn-secondary { background-color: #6b7280; color: white; }
.btn-warning { background-color: #f59e0b; color: white; }

.api-result {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #ecfdf5;
  border-left: 4px solid #10b981;
  border-radius: 4px;
  color: #065f46;
}

.api-error {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  border-radius: 4px;
  color: #991b1b;
}
</style>
