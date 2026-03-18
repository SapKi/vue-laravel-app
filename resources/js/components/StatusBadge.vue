<template>
  <span class="badge" :class="badgeClass">
    <span class="badge-dot"></span>
    {{ label }}
  </span>
</template>

<script setup>
const props = defineProps({
  status: { type: String, required: true },
});

const map = {
  pending:  { label: 'Pending',  cls: 'badge--pending' },
  approved: { label: 'Approved', cls: 'badge--approved' },
  rejected: { label: 'Rejected', cls: 'badge--rejected' },
};

const badgeClass = map[props.status]?.cls ?? '';
const label      = map[props.status]?.label ?? props.status;
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px 3px 7px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.badge--pending {
  background: #fde68a;
  color: #92400e;
  border: none;
}
.badge--pending .badge-dot {
  background: #d97706;
  animation: pulse-amber 1.8s infinite;
}

.badge--approved {
  background: #6ee7b7;
  color: #065f46;
  border: none;
}
.badge--approved .badge-dot { background: #059669; }

.badge--rejected {
  background: #fca5a5;
  color: #7f1d1d;
  border: none;
}
.badge--rejected .badge-dot { background: #dc2626; }

@keyframes pulse-amber {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}
</style>
