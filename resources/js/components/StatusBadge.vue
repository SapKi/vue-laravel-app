<template>
  <span class="badge" :class="badgeClass">{{ label }}</span>
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
  display: inline-block;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}
.badge--pending  { background: #fef3c7; color: #92400e; }
.badge--approved { background: #d1fae5; color: #065f46; }
.badge--rejected { background: #fee2e2; color: #991b1b; }
</style>
