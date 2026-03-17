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

          <!-- Notes list -->
          <div v-if="notes.length" class="notes-section">
            <div class="notes-label">💬 Notes</div>
            <div class="notes-list">
              <div v-for="n in notes" :key="n.id" class="note-item">
                <p class="note-body">{{ n.body }}</p>
                <div class="note-footer">
                  <span class="note-date">{{ formatDate(n.created_at) }}</span>
                  <button class="note-delete-btn" title="Delete note" @click="handleDeleteNote(n.id)">🗑</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Already reviewed banner -->
          <div v-if="item.status !== 'pending'" class="reviewed-banner" :class="`reviewed-banner--${item.status}`">
            <span>This item was <strong>{{ item.status }}</strong>{{ item.reviewed_at ? ' on ' + formatDate(item.reviewed_at) : '' }}.</span>
            <button class="reopen-btn" :disabled="reopening" @click="handleReopen">
              <span v-if="reopening" class="btn-spinner btn-spinner--dark"></span>
              {{ reopening ? 'Reopening…' : '↩ Reopen' }}
            </button>
          </div>

          <!-- Global error (always visible regardless of status) -->
          <Transition name="err">
            <div v-if="error" class="form-error">⚠️ {{ error }}</div>
          </Transition>

          <!-- Note area (only for pending) -->
          <div v-if="item.status === 'pending'" class="review-form">
            <label class="note-label">Note <span class="optional">(optional)</span></label>
            <textarea
              v-model="note"
              placeholder="Add context for this decision…"
              class="note-input"
              rows="3"
            />

            <!-- Save note row -->
            <div class="save-note-row">
              <Transition name="saved">
                <span v-if="noteSaved" class="note-saved">✓ Note saved</span>
              </Transition>
              <button
                class="btn btn--save-note"
                :disabled="submitting || !note.trim()"
                @click="handleSaveNote"
              >
                <span v-if="submitting && action === 'note'" class="btn-spinner"></span>
                {{ submitting && action === 'note' ? 'Saving…' : 'Save note' }}
              </button>
            </div>

            <div class="divider"></div>

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
import { ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import RiskBadge from '@/components/RiskBadge.vue';
import { reviewItem, reopenItem, saveNote as apiSaveNote, deleteNote as apiDeleteNote } from '@/api/items';

const props = defineProps({
  item: { type: Object, required: true },
});
const emit = defineEmits(['close', 'reviewed', 'reopened']);

const note       = ref('');
const notes      = ref(props.item.notes ?? []);
const submitting = ref(false);
const reopening  = ref(false);
const action     = ref('');
const error      = ref('');
const noteSaved  = ref(false);

watch(() => props.item, (newItem) => {
  notes.value = newItem.notes ?? [];
  note.value  = '';
  error.value = '';
});

async function submit(status) {
  submitting.value = true;
  action.value     = status;
  error.value      = '';
  try {
    const updated = await reviewItem(props.item.id, { status, note: note.value.trim() || null });
    emit('reviewed', updated);
  } catch (e) {
    const data = e.response?.data;
    error.value = (typeof data === 'object' ? data?.message : null) ?? `Request failed (${e.response?.status ?? 'network error'})`;

  } finally {
    submitting.value = false;
  }
}

async function handleSaveNote() {
  if (!note.value.trim()) return;
  submitting.value = true;
  action.value     = 'note';
  error.value      = '';
  try {
    const created = await apiSaveNote(props.item.id, note.value.trim());
    notes.value.push(created);
    note.value      = '';
    noteSaved.value = true;
    setTimeout(() => (noteSaved.value = false), 2500);
  } catch (e) {
    error.value = 'Could not save note.';
  } finally {
    submitting.value = false;
  }
}

async function handleReopen() {
  reopening.value = true;
  error.value = '';
  try {
    const updated = await reopenItem(props.item.id);
    emit('reopened', updated);
  } catch (e) {
    const data = e.response?.data;
    error.value = (typeof data === 'object' ? data?.message : null) ?? `Could not reopen (${e.response?.status ?? 'network error'})`;
  } finally {
    reopening.value = false;
  }
}

async function handleDeleteNote(noteId) {
  try {
    await apiDeleteNote(props.item.id, noteId);
    notes.value = notes.value.filter(n => n.id !== noteId);
  } catch {
    error.value = 'Could not delete note.';
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
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 1rem;
}

.modal {
  background: var(--bg-card);
  border-radius: 18px;
  width: 100%;
  max-width: 660px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 2rem;
  box-shadow: var(--shadow-md), 0 0 0 1px var(--border);
}

/* Scrollbar */
.modal::-webkit-scrollbar { width: 6px; }
.modal::-webkit-scrollbar-track { background: transparent; }
.modal::-webkit-scrollbar-thumb { background: var(--border-focus); border-radius: 3px; }

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
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.3rem;
}
.modal-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text);
  line-height: 1.3;
  letter-spacing: -0.02em;
}
.close-btn {
  background: var(--bg-input);
  border: 1px solid var(--border);
  width: 34px;
  height: 34px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--text-muted);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
}
.close-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}
.meta-date { font-size: 0.78rem; color: var(--text-faint); margin-left: auto; }

.flags-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}
.flags-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }
.flag-pill {
  font-size: 0.7rem;
  background: var(--primary-light);
  color: var(--primary);
  padding: 2px 9px;
  border-radius: 999px;
  font-weight: 600;
  border: 1px solid var(--border);
}

.suggestion-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  margin-bottom: 1.1rem;
  line-height: 1.4;
}
.suggestion-icon { font-size: 1rem; flex-shrink: 0; line-height: 1; }
.suggestion-sub { font-weight: 400; font-size: 0.82em; opacity: 0.8; }
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
  background: var(--bg-input);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.1rem;
}
.content-text {
  font-size: 0.925rem;
  line-height: 1.75;
  color: var(--text-muted);
  white-space: pre-wrap;
}

.notes-section {
  margin-bottom: 1.1rem;
}
.notes-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}
.notes-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.note-item {
  background: var(--bg-input);
  border: 1px solid var(--border);
  border-left: 3px solid var(--primary);
  border-radius: 8px;
  padding: 0.65rem 0.9rem;
}
.note-body {
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.55;
  margin-bottom: 0.4rem;
}
.note-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.note-date { font-size: 0.72rem; color: var(--text-faint); }
.note-delete-btn {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  border: 1.5px solid var(--border);
  background: var(--bg-input);
  color: var(--text-faint);
  font-size: 0.7rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  padding: 0;
}
.note-delete-btn:hover {
  border-color: #fca5a5;
  background: #fff5f5;
  color: #ef4444;
}

.reviewer-note {
  background: var(--bg-input);
  border-radius: 10px;
  padding: 0.85rem 1rem;
  margin-bottom: 1rem;
  border: 1px solid var(--border);
}
.reviewer-note-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  margin-bottom: 0.35rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.reviewer-note p { font-size: 0.9rem; color: var(--text-muted); }

.reviewed-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
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
.reopen-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.85rem;
  border-radius: 7px;
  font-size: 0.78rem;
  font-weight: 700;
  border: 1.5px solid currentColor;
  background: rgba(255,255,255,0.5);
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
  transition: background 0.15s;
  color: inherit;
  flex-shrink: 0;
}
.reopen-btn:hover:not(:disabled) { background: rgba(255,255,255,0.75); }
.reopen-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner--dark {
  width: 11px;
  height: 11px;
  border: 2px solid rgba(0,0,0,0.2);
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.review-form { margin-top: 1rem; }
.note-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.optional { font-weight: 400; color: var(--text-faint); text-transform: none; letter-spacing: 0; }

.note-input {
  width: 100%;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 0.7rem 0.875rem;
  font-size: 0.9rem;
  resize: vertical;
  font-family: inherit;
  margin-bottom: 0.85rem;
  box-sizing: border-box;
  background: var(--bg-input);
  transition: all 0.2s;
  color: var(--text);
}
.note-input:focus {
  outline: none;
  border-color: var(--border-focus);
  background: var(--bg-card);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-error {
  color: #dc2626;
  font-size: 0.85rem;
  margin: 0.75rem 0;
  background: #fff5f5;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid #fca5a5;
}

.save-note-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.note-saved {
  font-size: 0.8rem;
  font-weight: 600;
  color: #059669;
}
.saved-enter-active, .saved-leave-active { transition: opacity 0.3s; }
.saved-enter-from, .saved-leave-to { opacity: 0; }
.err-enter-active { transition: all 0.2s; }
.err-leave-active { transition: all 0.15s; }
.err-enter-from, .err-leave-to { opacity: 0; transform: translateY(-4px); }

.divider {
  border: none;
  border-top: 1px solid var(--border);
  margin-bottom: 1rem;
}

.action-btns { display: flex; gap: 0.75rem; justify-content: center; }
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

.btn--save-note {
  background: var(--bg-input);
  color: var(--text);
  border: 1.5px solid var(--border);
  padding: 0.45rem 1rem;
  font-size: 0.82rem;
}
.btn--save-note:hover:not(:disabled) { background: var(--bg-hover); }
.btn--save-note:disabled { opacity: 0.4; cursor: not-allowed; }

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
