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
  background: #fef9c3;
  color: #a16207;
  border: none;
}
.badge--pending .badge-dot {
  background: #eab308;
  animation: pulse-amber 1.8s infinite;
}

.badge--approved {
  background: #d1fae5;
  color: #065f46;
  border: none;
}
.badge--approved .badge-dot { background: #10b981; }

.badge--rejected {
  background: #fee2e2;
  color: #991b1b;
  border: none;
}
.badge--rejected .badge-dot { background: #ef4444; }

@keyframes pulse-amber {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}
</style>
