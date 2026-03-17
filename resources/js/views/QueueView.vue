<template>
  <div>
    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-left">
        <input
          v-model="store.filters.search"
          type="search"
          placeholder="Search title or content…"
          class="search-input"
          @input="debouncedLoad"
        />
      </div>
      <div class="toolbar-right">
        <!-- Status tabs -->
        <div class="tabs">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            class="tab"
            :class="{ 'tab--active': store.filters.status === tab.value }"
            @click="setStatus(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>
        <!-- Sort -->
        <select v-model="store.filters.sort" class="select" @change="store.loadItems()">
          <option value="created_at">Date</option>
          <option value="risk_score">Risk score</option>
          <option value="title">Title</option>
        </select>
        <button class="icon-btn" :title="store.filters.order === 'desc' ? 'Descending' : 'Ascending'" @click="toggleOrder">
          {{ store.filters.order === 'desc' ? '↓' : '↑' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="state-msg">Loading…</div>

    <!-- Error -->
    <div v-else-if="store.error" class="state-msg state-msg--error">{{ store.error }}</div>

    <!-- Empty -->
    <div v-else-if="store.items.length === 0" class="state-msg">
      No items found.
      <router-link to="/submit" class="link">Submit the first one →</router-link>
    </div>

    <!-- List -->
    <div v-else class="item-list">
      <div
        v-for="item in store.items"
        :key="item.id"
        class="item-card"
        :class="{ 'item-card--high-risk': item.risk_score >= 55 }"
        @click="openModal(item)"
      >
        <div class="item-card__header">
          <span class="item-card__title">{{ item.title }}</span>
          <div class="item-card__badges">
            <StatusBadge :status="item.status" />
            <RiskBadge :score="item.risk_score" :flags="item.flags ?? []" />
          </div>
        </div>
        <p class="item-card__preview">{{ truncate(item.content, 120) }}</p>
        <div class="item-card__footer">
          <span class="item-card__date">{{ formatDate(item.created_at) }}</span>
          <span v-if="item.suggested_action" class="item-card__suggestion" :class="`suggestion--${item.suggested_action}`">
            Suggested: {{ item.suggested_action }}
          </span>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <ItemDetailModal
      v-if="selectedItem"
      :item="selectedItem"
      @close="selectedItem = null"
      @reviewed="handleReview"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useItemsStore } from '@/stores/items';
import StatusBadge from '@/components/StatusBadge.vue';
import RiskBadge from '@/components/RiskBadge.vue';
import ItemDetailModal from '@/components/ItemDetailModal.vue';

const store = useItemsStore();
const selectedItem = ref(null);

const statusTabs = [
  { label: 'All',      value: '' },
  { label: 'Pending',  value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
];

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => store.loadItems(), 300);
}

function setStatus(value) {
  store.filters.status = value;
  store.loadItems();
}

function toggleOrder() {
  store.filters.order = store.filters.order === 'desc' ? 'asc' : 'desc';
  store.loadItems();
}

function openModal(item) {
  selectedItem.value = { ...item };
}

async function handleReview({ id, status, note }) {
  try {
    const updated = await store.submitReview(id, status, note);
    selectedItem.value = updated;
  } catch (e) {
    throw e;
  }
}

function truncate(text, len) {
  return text.length > len ? text.slice(0, len) + '…' : text;
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString();
}

onMounted(() => store.loadItems());
</script>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
.toolbar-right { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.search-input {
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.45rem 0.75rem;
  font-size: 0.9rem;
  width: 260px;
}
.search-input:focus { outline: 2px solid #4f46e5; border-color: transparent; }

.tabs { display: flex; gap: 2px; }
.tab {
  padding: 0.35rem 0.85rem;
  border: 1px solid #d1d5db;
  background: #fff;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.15s;
}
.tab--active { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.tab:hover:not(.tab--active) { background: #f5f3ff; }

.select {
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.35rem 0.5rem;
  font-size: 0.85rem;
  background: #fff;
}

.icon-btn {
  width: 32px;
  height: 32px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  font-size: 1rem;
}
.icon-btn:hover { background: #f5f3ff; }

.state-msg {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
  font-size: 1rem;
}
.state-msg--error { color: #dc2626; }
.link { color: #4f46e5; margin-left: 0.4rem; }

.item-list { display: flex; flex-direction: column; gap: 0.75rem; }

.item-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: box-shadow 0.15s, border-color 0.15s;
}
.item-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-color: #c7d2fe; }
.item-card--high-risk { border-left: 4px solid #dc2626; }

.item-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}
.item-card__title {
  font-weight: 600;
  font-size: 0.97rem;
  color: #1e1b4b;
  flex: 1;
}
.item-card__badges { display: flex; gap: 0.4rem; flex-shrink: 0; }
.item-card__preview { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.6rem; line-height: 1.5; }
.item-card__footer { display: flex; align-items: center; justify-content: space-between; }
.item-card__date { font-size: 0.78rem; color: #9ca3af; }
.item-card__suggestion {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 999px;
}
.suggestion--approve { background: #d1fae5; color: #065f46; }
.suggestion--reject  { background: #fee2e2; color: #991b1b; }
</style>
