<template>
  <div>
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Review Queue</h1>
        <p class="page-subtitle">{{ store.meta.total }} item{{ store.meta.total !== 1 ? 's' : '' }} total</p>
      </div>
      <router-link to="/submit" class="submit-btn">
        <span class="submit-btn-icon">＋</span>
        Submit new item
      </router-link>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input
          v-model="store.filters.search"
          type="search"
          placeholder="Search title or content…"
          class="search-input"
          @input="debouncedLoad"
        />
      </div>
      <div class="toolbar-right">
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
        <select v-model="store.filters.sort" class="select" @change="onSortChange">
          <option value="created_at">📅 Date</option>
          <option value="risk_score">⚡ Risk</option>
          <option value="title">🔤 Title</option>
        </select>
        <button class="order-btn" :title="store.filters.order === 'desc' ? 'Descending' : 'Ascending'" @click="toggleOrder">
          {{ store.filters.order === 'desc' ? '↓' : '↑' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="state-box">
      <div class="spinner"></div>
      <p>Loading items…</p>
    </div>

    <!-- Error -->
    <div v-else-if="store.error" class="state-box state-box--error">
      <div class="state-icon">⚠️</div>
      <p>{{ store.error }}</p>
    </div>

    <!-- Empty -->
    <div v-else-if="store.items.length === 0" class="state-box">
      <div class="state-icon">📭</div>
      <p>No items found.</p>
      <router-link to="/submit" class="empty-link">Submit the first one →</router-link>
    </div>

    <!-- List -->
    <TransitionGroup v-else name="list" tag="div" class="item-list">
      <div
        v-for="(item, i) in store.items"
        :key="item.id"
        class="item-card"
        :class="{
          'item-card--approved': item.status === 'approved',
          'item-card--rejected': item.status === 'rejected',
        }"
        :style="{ animationDelay: `${i * 50}ms` }"
        @click="openModal(item)"
      >
        <div class="item-card__header">
          <span class="item-card__title">{{ item.title }}</span>
          <div class="item-card__badges">
            <StatusBadge :status="item.status" />
            <RiskBadge :score="item.risk_score" :flags="item.flags ?? []" />
          </div>
        </div>
        <p class="item-card__preview">{{ truncate(item.content, 130) }}</p>
        <div class="item-card__footer">
          <span class="item-card__date">{{ formatDate(item.created_at) }}</span>
          <div class="item-card__footer-right">
            <span v-if="item.flags?.length" class="flags-inline">
              <span v-for="f in item.flags" :key="f" class="flag-chip">{{ f }}</span>
            </span>
            <span v-if="item.suggested_action" class="suggestion-chip" :class="`suggestion-chip--${item.suggested_action}`">
              Suggested: {{ item.suggested_action }}
            </span>
            <button
              class="delete-btn"
              title="Delete item"
              @click.stop="confirmDelete(item)"
            >🗑</button>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <!-- Pagination -->
    <div v-if="store.meta.last_page > 1" class="pagination">
      <button class="page-btn" :disabled="store.page === 1" @click="store.goToPage(store.page - 1)">‹</button>
      <template v-for="p in pageRange" :key="p">
        <span v-if="p === '...'" class="page-ellipsis">…</span>
        <button
          v-else
          class="page-btn"
          :class="{ 'page-btn--active': p === store.page }"
          @click="store.goToPage(p)"
        >{{ p }}</button>
      </template>
      <button class="page-btn" :disabled="store.page === store.meta.last_page" @click="store.goToPage(store.page + 1)">›</button>
    </div>

    <!-- Modal -->
    <ItemDetailModal
      v-if="selectedItem"
      :item="selectedItem"
      @close="selectedItem = null"
      @reviewed="handleReview"
      @reopened="handleReopen"
    />

    <!-- Delete confirmation dialog -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="itemToDelete" class="confirm-overlay" @click.self="itemToDelete = null">
          <div class="confirm-dialog" role="alertdialog">
            <div class="confirm-icon">🗑️</div>
            <h3 class="confirm-title">Delete item?</h3>
            <p class="confirm-msg">
              "<strong>{{ itemToDelete.title }}</strong>" will be permanently removed.
            </p>
            <div class="confirm-actions">
              <button class="confirm-btn confirm-btn--cancel" @click="itemToDelete = null">Cancel</button>
              <button class="confirm-btn confirm-btn--delete" :disabled="deleting" @click="handleDelete">
                <span v-if="deleting" class="btn-spinner"></span>
                {{ deleting ? 'Deleting…' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useItemsStore } from '@/stores/items';
import { fetchItem } from '@/api/items';
import StatusBadge from '@/components/StatusBadge.vue';
import RiskBadge from '@/components/RiskBadge.vue';
import ItemDetailModal from '@/components/ItemDetailModal.vue';

const store = useItemsStore();
const selectedItem = ref(null);
const itemToDelete = ref(null);
const deleting = ref(false);

const statusTabs = [
  { label: 'All',      value: '' },
  { label: 'Pending',  value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
];

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => store.loadItems(1), 300);
}

function setStatus(value) {
  store.filters.status = value;
  store.loadItems(1);
}

function onSortChange() {
  if (store.filters.sort === 'risk_score') {
    store.filters.order = 'desc';
  }
  store.loadItems(1);
}

function toggleOrder() {
  store.filters.order = store.filters.order === 'desc' ? 'asc' : 'desc';
  store.loadItems(1);
}

// build compact page range e.g. [1, '...', 4, 5, 6, '...', 12]
const pageRange = computed(() => {
  const total = store.meta.last_page;
  const cur   = store.page;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = new Set([1, total, cur, cur - 1, cur + 1].filter(p => p >= 1 && p <= total));
  const sorted = [...pages].sort((a, b) => a - b);
  const result = [];
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...');
    result.push(sorted[i]);
  }
  return result;
});

async function openModal(item) {
  selectedItem.value = await fetchItem(item.id);
}

function handleReview(updated) {
  store.patchItem(updated);
  selectedItem.value = updated;
}

function handleReopen(updated) {
  store.patchItem(updated);
  selectedItem.value = updated;
}

function confirmDelete(item) {
  itemToDelete.value = item;
}

async function handleDelete() {
  if (!itemToDelete.value) return;
  deleting.value = true;
  try {
    await store.removeItem(itemToDelete.value.id);
    itemToDelete.value = null;
  } finally {
    deleting.value = false;
  }
}

function truncate(text, len) {
  return text.length > len ? text.slice(0, len) + '…' : text;
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(() => store.loadItems());
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 1.75rem;
}
.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  background: var(--primary-grad);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.03em;
}
.page-subtitle { color: var(--text-faint); font-size: 0.875rem; margin-top: 2px; }

.submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.4rem;
  background: var(--primary-grad);
  color: #fff;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: var(--shadow-md);
  transition: all 0.2s;
  white-space: nowrap;
}
.submit-btn:hover {
  transform: translateY(-2px);
  filter: brightness(1.08);
}
.submit-btn-icon { font-size: 1.1rem; line-height: 1; }

/* Toolbar */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  background: var(--bg-card);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
}
.toolbar-right { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.search-wrap { position: relative; }
.search-icon {
  position: absolute;
  left: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.85rem;
  pointer-events: none;
}
.search-input {
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.45rem 0.75rem 0.45rem 2rem;
  font-size: 0.875rem;
  width: 240px;
  background: var(--bg-input);
  color: var(--text);
  transition: all 0.2s;
  font-family: inherit;
}
.search-input:focus {
  outline: none;
  border-color: var(--border-focus);
  background: var(--bg-card);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.tabs { display: flex; gap: 3px; }
.tab {
  padding: 0.35rem 0.9rem;
  border: 1.5px solid var(--border);
  background: var(--bg-input);
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s;
  font-family: inherit;
  color: var(--text-muted);
}
.tab--active {
  background: var(--primary-grad);
  color: #fff;
  border-color: transparent;
  box-shadow: var(--shadow-sm);
}
.tab:hover:not(.tab--active) {
  background: var(--bg-hover);
  color: var(--primary);
  border-color: var(--border-focus);
}

.select {
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font-size: 0.8rem;
  background: var(--bg-input);
  font-family: inherit;
  color: var(--text);
  cursor: pointer;
}
.select:focus { outline: none; border-color: var(--border-focus); }

.order-btn {
  width: 34px;
  height: 34px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  background: var(--bg-input);
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.18s;
  color: var(--primary);
}
.order-btn:hover { background: var(--bg-hover); border-color: var(--border-focus); }

/* States */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 1rem;
  gap: 0.75rem;
  color: var(--text-faint);
  background: var(--bg-card);
  border-radius: 16px;
  border: 1px dashed var(--border);
}
.state-box--error { color: #dc2626; border-color: #fca5a5; background: #fff5f5; }
.state-icon { font-size: 2.5rem; }
.empty-link {
  color: var(--primary);
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  margin-top: 0.25rem;
}
.empty-link:hover { text-decoration: underline; }

/* Spinner */
.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid var(--border);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Cards */
.item-list { display: flex; flex-direction: column; gap: 0.75rem; }

.item-card {
  background: var(--bg-card);
  border: 1.5px solid var(--border);
  border-radius: 14px;
  padding: 1.1rem 1.4rem;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s, z-index 0s;
  animation: card-in 0.35s both ease-out;
  position: relative;
  z-index: 1;
}
.item-card::before {
  content: '';
  position: absolute;
  top: 6px;
  left: 0;
  width: 4px;
  height: calc(100% - 12px);
  background: var(--primary-grad);
  border-radius: 0 3px 3px 0;
  opacity: 0;
  transition: opacity 0.2s;
}
.item-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--border-focus);
  z-index: 10;
}
.item-card:hover::before { opacity: 1; }

.item-card--approved {
  border-color: #6ee7b7;
}
.item-card--approved::before {
  background: linear-gradient(180deg, #10b981, #059669);
  opacity: 1;
}
.item-card--rejected {
  border-color: #fca5a5;
}
.item-card--rejected::before {
  background: linear-gradient(180deg, #ef4444, #b91c1c);
  opacity: 1;
}

.item-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.item-card__title {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--text);
  flex: 1;
  line-height: 1.4;
}
.item-card__badges { display: flex; gap: 0.4rem; flex-shrink: 0; }
.item-card__preview {
  font-size: 0.855rem;
  color: var(--text-muted);
  margin-bottom: 0.75rem;
  line-height: 1.55;
}

.item-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.item-card__date { font-size: 0.775rem; color: var(--text-faint); }
.item-card__footer-right { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }

.flags-inline { display: flex; gap: 3px; }
.flag-chip {
  font-size: 0.68rem;
  background: var(--primary-light);
  color: var(--primary);
  padding: 1px 7px;
  border-radius: 999px;
  font-weight: 600;
  border: 1px solid var(--border);
}

.suggestion-chip {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 999px;
  letter-spacing: 0.02em;
}
.suggestion-chip--approve {
  background: #d1fae5;
  color: #065f46;
  border: none;
}
.suggestion-chip--reject {
  background: #fee2e2;
  color: #991b1b;
  border: none;
}

/* Animations */
@keyframes card-in {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

.list-enter-active { transition: all 0.3s ease-out; }
.list-leave-active { transition: all 0.2s ease-in; position: absolute; width: 100%; }
.list-enter-from { opacity: 0; transform: translateY(16px); }
.list-leave-to   { opacity: 0; transform: translateX(30px); }
.list-move { transition: transform 0.3s; }

/* Delete button */
.delete-btn {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  border: 1.5px solid var(--border);
  background: var(--bg-input);
  color: var(--text-faint);
  font-size: 0.8rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-left: 4px;
  transition: all 0.15s;
}
.delete-btn:hover {
  border-color: #fca5a5;
  background: #fff5f5;
  color: #ef4444;
}

/* Confirm dialog */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 300;
  padding: 1rem;
}
.confirm-dialog {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 2rem 2rem 1.5rem;
  width: 100%;
  max-width: 380px;
  text-align: center;
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  border: 1px solid var(--border);
}
.confirm-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
.confirm-title { font-size: 1.15rem; font-weight: 800; color: var(--text); margin-bottom: 0.5rem; }
.confirm-msg { font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem; }
.confirm-msg strong { color: var(--text); }

.confirm-actions { display: flex; gap: 0.75rem; }
.confirm-btn {
  flex: 1;
  padding: 0.65rem 1rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  transition: all 0.15s;
}
.confirm-btn--cancel { background: var(--bg-input); color: var(--text); border: 1px solid var(--border); }
.confirm-btn--cancel:hover { background: var(--bg-hover); }
.confirm-btn--delete {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  box-shadow: 0 4px 14px rgba(239,68,68,0.35);
}
.confirm-btn--delete:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(239,68,68,0.5);
}
.confirm-btn--delete:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-spinner {
  width: 13px; height: 13px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  margin-top: 1.5rem;
}
.page-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 0.5rem;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  background: var(--bg-card);
  color: var(--text-muted);
  font-size: 0.875rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s;
}
.page-btn:hover:not(:disabled):not(.page-btn--active) {
  background: var(--bg-hover);
  border-color: var(--border-focus);
  color: var(--primary);
}
.page-btn--active {
  background: var(--primary-grad);
  border-color: transparent;
  color: #fff;
  cursor: default;
}
.page-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.page-ellipsis { color: var(--text-faint); font-size: 0.875rem; padding: 0 0.2rem; line-height: 34px; }

.modal-enter-active { animation: modal-in 0.22s ease-out; }
.modal-leave-active { animation: modal-in 0.18s ease-in reverse; }
@keyframes modal-in {
  from { opacity: 0; transform: scale(0.94) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
