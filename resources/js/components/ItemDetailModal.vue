<template>
  <div class="overlay" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true">

      <!-- Header -->
      <div class="modal-header">
        <h2 class="modal-title">{{ item.title }}</h2>
        <button class="close-btn" @click="$emit('close')" aria-label="Close">&times;</button>
      </div>

      <!-- Meta row -->
      <div class="meta-row">
        <StatusBadge :status="item.status" />
        <RiskBadge :score="item.risk_score" :flags="item.flags ?? []" />
        <span class="meta-date">{{ formatDate(item.created_at) }}</span>
      </div>

      <!-- Flags -->
      <div v-if="item.flags?.length" class="flags">
        <span v-for="f in item.flags" :key="f" class="flag-chip">{{ f }}</span>
      </div>

      <!-- Suggested action banner -->
      <div v-if="item.suggested_action && item.status === 'pending'" class="suggestion" :class="`suggestion--${item.suggested_action}`">
        Auto-suggestion: <strong>{{ item.suggested_action === 'approve' ? 'Approve' : 'Reject' }}</strong>
        &mdash; based on content analysis.
      </div>

      <!-- Content -->
      <p class="item-content">{{ item.content }}</p>

      <!-- Reviewer note (already reviewed) -->
      <div v-if="item.status !== 'pending' && item.reviewer_note" class="reviewer-note">
        <strong>Reviewer note:</strong> {{ item.reviewer_note }}
      </div>

      <!-- Review form (pending only) -->
      <div v-if="item.status === 'pending'" class="review-form">
        <textarea
          v-model="note"
          placeholder="Optional note…"
          class="note-input"
          rows="3"
        />
        <div v-if="error" class="form-error">{{ error }}</div>
        <div class="action-btns">
          <button class="btn btn--approve" :disabled="submitting" @click="submit('approved')">
            {{ submitting && action === 'approved' ? 'Approving…' : 'Approve' }}
          </button>
          <button class="btn btn--reject" :disabled="submitting" @click="submit('rejected')">
            {{ submitting && action === 'rejected' ? 'Rejecting…' : 'Reject' }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import RiskBadge from '@/components/RiskBadge.vue';

const props = defineProps({
  item: { type: Object, required: true },
});
const emit = defineEmits(['close', 'reviewed']);

const note      = ref('');
const submitting = ref(false);
const action     = ref('');
const error      = ref('');

async function submit(status) {
  submitting.value = true;
  action.value     = status;
  error.value      = '';
  try {
    await emit('reviewed', { id: props.item.id, status, note: note.value });
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Something went wrong.';
  } finally {
    submitting.value = false;
  }
}

function formatDate(iso) {
  return new Date(iso).toLocaleString();
}
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}
.modal {
  background: #fff;
  border-radius: 10px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 1.75rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}
.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e1b4b;
  flex: 1;
  margin-right: 1rem;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
  padding: 0;
}
.close-btn:hover { color: #111; }

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}
.meta-date { font-size: 0.8rem; color: #9ca3af; margin-left: auto; }

.flags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 0.75rem;
}
.flag-chip {
  font-size: 0.7rem;
  background: #ede9fe;
  color: #5b21b6;
  padding: 2px 8px;
  border-radius: 999px;
  font-weight: 500;
}

.suggestion {
  padding: 0.6rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}
.suggestion--approve { background: #d1fae5; color: #065f46; }
.suggestion--reject  { background: #fee2e2; color: #991b1b; }

.item-content {
  font-size: 0.95rem;
  line-height: 1.7;
  color: #374151;
  white-space: pre-wrap;
  margin-bottom: 1.25rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 6px;
}

.reviewer-note {
  font-size: 0.875rem;
  color: #4b5563;
  background: #f3f4f6;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.review-form { margin-top: 0.5rem; }
.note-input {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
  resize: vertical;
  font-family: inherit;
  margin-bottom: 0.75rem;
  box-sizing: border-box;
}
.note-input:focus { outline: 2px solid #4f46e5; border-color: transparent; }

.form-error { color: #dc2626; font-size: 0.85rem; margin-bottom: 0.5rem; }

.action-btns { display: flex; gap: 0.75rem; }
.btn {
  padding: 0.55rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn--approve { background: #059669; color: #fff; }
.btn--approve:hover:not(:disabled) { background: #047857; }
.btn--reject  { background: #dc2626; color: #fff; }
.btn--reject:hover:not(:disabled)  { background: #b91c1c; }
</style>
