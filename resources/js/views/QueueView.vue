<template>
  <div>
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Review Queue</h1>
        <p class="page-subtitle">{{ store.items.length }} item{{ store.items.length !== 1 ? 's' : '' }} loaded</p>
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
        <select v-model="store.filters.sort" class="select" @change="store.loadItems()">
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
          'item-card--high-risk': item.risk_score >= 55,
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

    <!-- Modal -->
    <ItemDetailModal
      v-if="selectedItem"
      :item="selectedItem"
      @close="selectedItem = null"
      @reviewed="handleReview"
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
import { ref, onMounted } from 'vue';
import { useItemsStore } from '@/stores/items';
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
  const updated = await store.submitReview(id, status, note);
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
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.03em;
}
.page-subtitle { color: #9ca3af; font-size: 0.875rem; margin-top: 2px; }

.submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: 0 4px 18px rgba(99, 102, 241, 0.4);
  transition: all 0.2s;
  white-space: nowrap;
}
.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(99, 102, 241, 0.55);
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
  background: #fff;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(79, 70, 229, 0.08);
  border: 1px solid #e0e7ff;
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
  border: 1.5px solid #e0e7ff;
  border-radius: 8px;
  padding: 0.45rem 0.75rem 0.45rem 2rem;
  font-size: 0.875rem;
  width: 240px;
  background: #fafaff;
  transition: all 0.2s;
  font-family: inherit;
}
.search-input:focus {
  outline: none;
  border-color: #6366f1;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.tabs { display: flex; gap: 3px; }
.tab {
  padding: 0.35rem 0.9rem;
  border: 1.5px solid #e0e7ff;
  background: #fafaff;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s;
  font-family: inherit;
  color: #6b7280;
}
.tab--active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 10px rgba(99, 102, 241, 0.4);
}
.tab:hover:not(.tab--active) {
  background: #ede9fe;
  color: #4f46e5;
  border-color: #c4b5fd;
}

.select {
  border: 1.5px solid #e0e7ff;
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font-size: 0.8rem;
  background: #fafaff;
  font-family: inherit;
  color: #374151;
  cursor: pointer;
}
.select:focus { outline: none; border-color: #6366f1; }

.order-btn {
  width: 34px;
  height: 34px;
  border: 1.5px solid #e0e7ff;
  border-radius: 8px;
  background: #fafaff;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.18s;
  color: #4f46e5;
}
.order-btn:hover { background: #ede9fe; border-color: #c4b5fd; }

/* States */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 1rem;
  gap: 0.75rem;
  color: #9ca3af;
  background: #fff;
  border-radius: 16px;
  border: 1px dashed #e0e7ff;
}
.state-box--error { color: #dc2626; border-color: #fca5a5; background: #fff5f5; }
.state-icon { font-size: 2.5rem; }
.empty-link {
  color: #6366f1;
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
  border: 3px solid #e0e7ff;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Cards */
.item-list { display: flex; flex-direction: column; gap: 0.75rem; }

.item-card {
  background: #fff;
  border: 1.5px solid #e0e7ff;
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
  top: 0; left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #6366f1, #8b5cf6);
  opacity: 0;
  transition: opacity 0.2s;
}
.item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(99, 102, 241, 0.15);
  border-color: #c4b5fd;
  z-index: 10;
}
.item-card:hover::before { opacity: 1; }

.item-card--high-risk {
  border-color: #fca5a5;
}
.item-card--high-risk::before {
  background: linear-gradient(180deg, #ef4444, #dc2626);
  opacity: 1;
}
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
  color: #1e1b4b;
  flex: 1;
  line-height: 1.4;
}
.item-card__badges { display: flex; gap: 0.4rem; flex-shrink: 0; }
.item-card__preview {
  font-size: 0.855rem;
  color: #6b7280;
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
.item-card__date { font-size: 0.775rem; color: #9ca3af; }
.item-card__footer-right { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }

.flags-inline { display: flex; gap: 3px; }
.flag-chip {
  font-size: 0.68rem;
  background: #ede9fe;
  color: #5b21b6;
  padding: 1px 7px;
  border-radius: 999px;
  font-weight: 600;
  border: 1px solid #ddd6fe;
}

.suggestion-chip {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 999px;
  letter-spacing: 0.02em;
}
.suggestion-chip--approve {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #065f46;
  border: 1px solid #6ee7b7;
}
.suggestion-chip--reject {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #991b1b;
  border: 1px solid #fca5a5;
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
  border: 1.5px solid #fca5a5;
  background: #fff0f0;
  color: #ef4444;
  font-size: 0.8rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-left: 4px;
  transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
}
.delete-btn:hover {
  background: #ef4444;
  border-color: #ef4444;
  color: #fff;
  transform: scale(1.1);
  box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4);
}

/* Confirm dialog */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,10,40,0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 300;
  padding: 1rem;
}
.confirm-dialog {
  background: #fff;
  border-radius: 16px;
  padding: 2rem 2rem 1.5rem;
  width: 100%;
  max-width: 380px;
  text-align: center;
  box-shadow: 0 24px 64px rgba(0,0,0,0.2);
}
.confirm-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
.confirm-title { font-size: 1.15rem; font-weight: 800; color: #1e1b4b; margin-bottom: 0.5rem; }
.confirm-msg { font-size: 0.875rem; color: #6b7280; line-height: 1.5; margin-bottom: 1.5rem; }
.confirm-msg strong { color: #374151; }

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
.confirm-btn--cancel { background: #f3f4f6; color: #374151; }
.confirm-btn--cancel:hover { background: #e5e7eb; }
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

.modal-enter-active { animation: modal-in 0.22s ease-out; }
.modal-leave-active { animation: modal-in 0.18s ease-in reverse; }
@keyframes modal-in {
  from { opacity: 0; transform: scale(0.94) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
