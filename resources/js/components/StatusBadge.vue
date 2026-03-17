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
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
.badge--pending .badge-dot {
  background: #f59e0b;
  animation: pulse-amber 1.8s infinite;
}

.badge--approved {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #065f46;
  border: 1px solid #6ee7b7;
}
.badge--approved .badge-dot { background: #10b981; }

.badge--rejected {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #991b1b;
  border: 1px solid #fca5a5;
}
.badge--rejected .badge-dot { background: #ef4444; }

@keyframes pulse-amber {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}
</style>
