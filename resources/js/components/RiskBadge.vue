<template>
  <span ref="badgeRef" class="risk-wrap" @mouseenter="open" @mouseleave="close">
    <span class="risk" :class="riskClass">
      <span class="risk-dot" :class="riskClass + '-dot'"></span>
      Risk {{ score }}
    </span>

    <Teleport to="body">
      <Transition name="pop">
        <div
          v-if="show"
          class="popover"
          role="tooltip"
          :style="popoverStyle"
        >
          <div class="popover-arrow"></div>
          <div class="popover-header">
            <span class="popover-score" :class="riskClass">{{ score }}/100</span>
            <span class="popover-label">{{ levelLabel }}</span>
          </div>
          <div class="popover-body">
            <div v-if="!flagRows.length" class="popover-clean">
              ✅ No issues detected
            </div>
            <div v-for="row in flagRows" :key="row.flag" class="popover-row">
              <span class="popover-flag-icon">{{ row.icon }}</span>
              <div>
                <div class="popover-flag-name">{{ row.label }}</div>
                <div class="popover-flag-desc">{{ row.desc }}</div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </span>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  score: { type: Number, required: true },
  flags: { type: Array, default: () => [] },
});

const show      = ref(false);
const badgeRef  = ref(null);
const popoverStyle = ref({});

const FLAG_META = {
  spam:      { icon: '📢', label: 'Spam keywords',      desc: 'Contains known spam phrases (e.g. "buy now", "free money").' },
  offensive: { icon: '⚠️', label: 'Offensive language', desc: 'Contains words associated with offensive or hostile content.' },
  caps_heavy:{ icon: '🔠', label: 'Excessive caps',      desc: 'More than 40% of letters are uppercase — common in spam.' },
  has_urls:  { icon: '🔗', label: 'Contains URLs',       desc: 'URLs were detected in the content.' },
  very_short:{ icon: '📏', label: 'Very short content',  desc: 'Content is unusually short (under 15 characters).' },
};

const flagRows = computed(() =>
  (props.flags ?? []).map(f => ({ flag: f, ...(FLAG_META[f] ?? { icon: '🏷', label: f, desc: '' }) }))
);

const riskClass = computed(() => {
  if (props.score >= 55) return 'risk--high';
  if (props.score >= 25) return 'risk--medium';
  return 'risk--low';
});

const levelLabel = computed(() => {
  if (props.score >= 55) return 'High risk';
  if (props.score >= 25) return 'Medium risk';
  return 'Low risk';
});

function open() {
  if (!badgeRef.value) return;
  const rect = badgeRef.value.getBoundingClientRect();
  popoverStyle.value = {
    position: 'fixed',
    bottom: `${window.innerHeight - rect.top + 10}px`,
    left:   `${rect.left + rect.width / 2}px`,
    transform: 'translateX(-50%)',
    zIndex: 9999,
  };
  show.value = true;
}

function close() {
  show.value = false;
}
</script>

<style scoped>
.risk-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.risk {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px 3px 7px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: default;
  white-space: nowrap;
  letter-spacing: 0.02em;
  user-select: none;
}

.risk-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.risk--low    { background: linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; border:1px solid #6ee7b7; }
.risk--medium { background: linear-gradient(135deg,#fef3c7,#fde68a); color:#78350f; border:1px solid #fcd34d; }
.risk--high   { background: linear-gradient(135deg,#fee2e2,#fecaca); color:#991b1b; border:1px solid #fca5a5; }

.risk--low-dot    { background: #10b981; }
.risk--medium-dot { background: #f59e0b; animation: pulse 1.6s infinite; }
.risk--high-dot   { background: #ef4444; animation: pulse 1s infinite; }
</style>

<!-- Popover styles must be global (not scoped) because it's teleported to body -->
<style>
.popover {
  width: 260px;
  background: #1e293b;
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(0,0,0,0.4);
  overflow: hidden;
  pointer-events: none;
}

.popover-arrow {
  position: absolute;
  bottom: -6px;
  left: 50%;
  transform: translateX(-50%) rotate(45deg);
  width: 12px;
  height: 12px;
  background: #1e293b;
  border-radius: 2px;
}

.popover-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.65rem 0.9rem 0.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.popover-score {
  font-size: 1.1rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 6px;
}
.risk--low.popover-score    { background: #d1fae5; color: #065f46; }
.risk--medium.popover-score { background: #fef3c7; color: #78350f; }
.risk--high.popover-score   { background: #fee2e2; color: #991b1b; }

.popover-label { font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.6); }

.popover-body { padding: 0.6rem 0.9rem 0.8rem; display: flex; flex-direction: column; gap: 0.55rem; }

.popover-clean { font-size: 0.82rem; color: #6ee7b7; text-align: center; padding: 0.25rem 0; }

.popover-row { display: flex; align-items: flex-start; gap: 0.5rem; }
.popover-flag-icon { font-size: 0.9rem; flex-shrink: 0; margin-top: 1px; }
.popover-flag-name { font-size: 0.78rem; font-weight: 700; color: #fff; margin-bottom: 1px; }
.popover-flag-desc { font-size: 0.72rem; color: rgba(255,255,255,0.55); line-height: 1.4; }

.pop-enter-active { transition: opacity 0.15s, transform 0.15s; }
.pop-leave-active { transition: opacity 0.1s, transform 0.1s; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateX(-50%) translateY(4px); }

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.75); }
}
</style>
