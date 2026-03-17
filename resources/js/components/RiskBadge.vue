<template>
  <span class="risk" :class="riskClass" :title="title">
    Risk {{ score }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  score: { type: Number, required: true },
  flags: { type: Array, default: () => [] },
});

const riskClass = computed(() => {
  if (props.score >= 55) return 'risk--high';
  if (props.score >= 25) return 'risk--medium';
  return 'risk--low';
});

const title = computed(() =>
  props.flags?.length ? `Flags: ${props.flags.join(', ')}` : 'No flags'
);
</script>

<style scoped>
.risk {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: default;
}
.risk--low    { background: #d1fae5; color: #065f46; }
.risk--medium { background: #fef3c7; color: #92400e; }
.risk--high   { background: #fee2e2; color: #991b1b; }
</style>
