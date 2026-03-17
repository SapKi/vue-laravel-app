<template>
  <Teleport to="body">
    <Transition name="modal">
      <div class="overlay" @click.self="$emit('close')">
        <div class="modal" role="dialog" aria-modal="true">

          <!-- Header -->
          <div class="modal-header">
            <div class="modal-header-left">
              <div class="modal-tag">Item #{{ item.id }}</div>
              <h2 class="modal-title">{{ item.title }}</h2>
            </div>
            <button class="close-btn" @click="$emit('close')" aria-label="Close">✕</button>
          </div>

          <!-- Meta row -->
          <div class="meta-row">
            <StatusBadge :status="item.status" />
            <RiskBadge :score="item.risk_score" :flags="item.flags ?? []" />
            <span class="meta-date">{{ formatDate(item.created_at) }}</span>
          </div>

          <!-- Flags -->
          <div v-if="item.flags?.length" class="flags-row">
            <span class="flags-label">Flags:</span>
            <span v-for="f in item.flags" :key="f" class="flag-pill">{{ f }}</span>
          </div>

          <!-- Suggestion banner -->
          <div v-if="item.suggested_action && item.status === 'pending'" class="suggestion-banner" :class="`suggestion-banner--${item.suggested_action}`">
            <span class="suggestion-icon">{{ item.suggested_action === 'approve' ? '✅' : '🚨' }}</span>
            <div>
              <strong>Auto-suggestion: {{ item.suggested_action === 'approve' ? 'Approve' : 'Reject' }}</strong>
              <span> — based on content analysis</span>
            </div>
          </div>

          <!-- Content -->
          <div class="content-box">
            <p class="content-text">{{ item.content }}</p>
          </div>

          <!-- Reviewer note (already reviewed) -->
          <div v-if="item.status !== 'pending' && item.reviewer_note" class="reviewer-note">
            <span class="reviewer-note-label">💬 Reviewer note</span>
            <p>{{ item.reviewer_note }}</p>
          </div>

          <!-- Already reviewed banner -->
          <div v-if="item.status !== 'pending'" class="reviewed-banner" :class="`reviewed-banner--${item.status}`">
            This item was <strong>{{ item.status }}</strong>{{ item.reviewed_at ? ' on ' + formatDate(item.reviewed_at) : '' }}.
          </div>

          <!-- Review form -->
          <div v-if="item.status === 'pending'" class="review-form">
            <label class="note-label">Leave a note <span class="optional">(optional)</span></label>
            <textarea
              v-model="note"
              placeholder="Add context for this decision…"
              class="note-input"
              rows="3"
            />
            <div v-if="error" class="form-error">⚠️ {{ error }}</div>
            <div class="action-btns">
              <button class="btn btn--approve" :disabled="submitting" @click="submit('approved')">
                <span v-if="submitting && action === 'approved'" class="btn-spinner"></span>
                <span v-else>✓</span>
                {{ submitting && action === 'approved' ? 'Approving…' : 'Approve' }}
              </button>
              <button class="btn btn--reject" :disabled="submitting" @click="submit('rejected')">
                <span v-if="submitting && action === 'rejected'" class="btn-spinner"></span>
                <span v-else>✕</span>
                {{ submitting && action === 'rejected' ? 'Rejecting…' : 'Reject' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import RiskBadge from '@/components/RiskBadge.vue';

const props = defineProps({
  item: { type: Object, required: true },
});
const emit = defineEmits(['close', 'reviewed']);

const note       = ref('');
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
  return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 10, 40, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 1rem;
}

.modal {
  background: #fff;
  border-radius: 18px;
  width: 100%;
  max-width: 660px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 2rem;
  box-shadow: 0 30px 80px rgba(79, 70, 229, 0.25), 0 0 0 1px rgba(99,102,241,0.1);
}

/* Scrollbar */
.modal::-webkit-scrollbar { width: 6px; }
.modal::-webkit-scrollbar-track { background: transparent; }
.modal::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 3px; }

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}
.modal-header-left { flex: 1; }
.modal-tag {
  font-size: 0.72rem;
  font-weight: 700;
  color: #8b5cf6;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.3rem;
}
.modal-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: #1e1b4b;
  line-height: 1.3;
  letter-spacing: -0.02em;
}
.close-btn {
  background: #f3f4f6;
  border: none;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
}
.close-btn:hover { background: #fee2e2; color: #dc2626; }

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}
.meta-date { font-size: 0.78rem; color: #9ca3af; margin-left: auto; }

.flags-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}
.flags-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; }
.flag-pill {
  font-size: 0.7rem;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #5b21b6;
  padding: 2px 9px;
  border-radius: 999px;
  font-weight: 600;
  border: 1px solid #c4b5fd;
}

.suggestion-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.8rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  margin-bottom: 1.1rem;
}
.suggestion-icon { font-size: 1.1rem; flex-shrink: 0; }
.suggestion-banner--approve {
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  color: #065f46;
  border: 1px solid #a7f3d0;
}
.suggestion-banner--reject {
  background: linear-gradient(135deg, #fff5f5, #fee2e2);
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.content-box {
  background: linear-gradient(135deg, #fafaff, #f5f3ff);
  border: 1px solid #e0e7ff;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.1rem;
}
.content-text {
  font-size: 0.925rem;
  line-height: 1.75;
  color: #374151;
  white-space: pre-wrap;
}

.reviewer-note {
  background: #f9fafb;
  border-radius: 10px;
  padding: 0.85rem 1rem;
  margin-bottom: 1rem;
  border: 1px solid #e5e7eb;
}
.reviewer-note-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #6b7280;
  margin-bottom: 0.35rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.reviewer-note p { font-size: 0.9rem; color: #374151; }

.reviewed-banner {
  text-align: center;
  padding: 0.7rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
.reviewed-banner--approved {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #065f46;
  border: 1px solid #6ee7b7;
}
.reviewed-banner--rejected {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.review-form { margin-top: 1rem; }
.note-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.optional { font-weight: 400; color: #9ca3af; text-transform: none; letter-spacing: 0; }

.note-input {
  width: 100%;
  border: 1.5px solid #e0e7ff;
  border-radius: 10px;
  padding: 0.7rem 0.875rem;
  font-size: 0.9rem;
  resize: vertical;
  font-family: inherit;
  margin-bottom: 0.85rem;
  box-sizing: border-box;
  background: #fafaff;
  transition: all 0.2s;
  color: #1e1b4b;
}
.note-input:focus {
  outline: none;
  border-color: #6366f1;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.form-error {
  color: #dc2626;
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  background: #fff5f5;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid #fca5a5;
}

.action-btns { display: flex; gap: 0.75rem; }
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.75rem;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.btn--approve {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}
.btn--approve:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(16, 185, 129, 0.5);
}

.btn--reject {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}
.btn--reject:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(239, 68, 68, 0.5);
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Modal transition */
.modal-enter-active { animation: modal-in 0.25s ease-out; }
.modal-leave-active { animation: modal-in 0.2s ease-in reverse; }

@keyframes modal-in {
  from { opacity: 0; transform: scale(0.94) translateY(16px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
