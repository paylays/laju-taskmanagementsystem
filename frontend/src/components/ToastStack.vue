<script setup>
import { useToasts } from '../composables/useToasts'

const { toasts, dismissToast, host } = useToasts()

const LABELS = { success: 'Success', info: 'Info', error: 'Error' }
</script>

<template>
  <Teleport :to="host ?? 'body'">
    <div class="toasts" :class="{ 'in-panel': host }" aria-live="polite">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast"
        :class="`is-${t.kind}`"
        :role="t.kind === 'error' ? 'alert' : 'status'"
      >
        <span class="marker" aria-hidden="true"></span>
        <p>
          <span class="label">{{ LABELS[t.kind] }}</span>
          {{ t.message }}
        </p>
        <button type="button" class="close" :aria-label="`Close: ${t.message}`" @click="dismissToast(t.id)">Close</button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.toasts {
  position: fixed;
  z-index: 50;
  right: var(--space-4);
  bottom: calc(var(--space-4) + env(safe-area-inset-bottom, 0px));
  display: grid;
  gap: var(--space-2);
  width: min(24rem, calc(100% - 2 * var(--space-4)));
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-1) var(--space-3) var(--space-3);
  background: var(--paper);
  color: var(--ink);
  border: 1px solid var(--ink);
}

/* Square marker: ink for success, outline for info, red for errors. The label says the same in words. */
.marker {
  flex: 0 0 0.625rem;
  height: 0.625rem;
  margin-top: 0.375rem;
  border: 1px solid var(--ink);
}

.is-success .marker {
  background: var(--ink);
}

.is-error {
  border-color: var(--danger);
}

.is-error .marker {
  background: var(--danger);
  border-color: var(--danger);
}

p {
  flex: 1;
  min-width: 0;
  overflow-wrap: anywhere;
}

.label {
  display: block;
  font-weight: 400;
}

.is-error .label {
  color: var(--danger);
}

.close {
  flex: 0 0 auto;
  min-height: var(--control-h);
  margin-top: calc(-1 * var(--space-2));
  padding: 0 var(--space-3);
  border: 0;
  background: transparent;
  color: var(--ink);
  font: inherit;
  font-size: var(--text-sm);
  font-weight: 400;
  cursor: pointer;
}

.close:hover {
  background: var(--lane);
}

@media (max-width: 599px) {
  .toasts {
    right: var(--space-2);
    left: var(--space-2);
    width: auto;
  }
}

/* Inside the task panel's sticky slot: in the flow at the panel's bottom edge. */
.toasts.in-panel {
  position: static;
  width: auto;
  padding: 0 var(--space-4) calc(var(--space-4) + env(safe-area-inset-bottom, 0px));
}

.toasts.in-panel:empty {
  display: none;
}

/* Wide screens leave room beside the panel, so the toasts sit there and cover nothing. */
@media (min-width: 992px) {
  .toasts.in-panel {
    position: fixed;
    left: var(--space-4);
    right: auto;
    bottom: calc(var(--space-4) + env(safe-area-inset-bottom, 0px));
    width: min(24rem, calc(100vw - 36rem - 3rem));
    padding: 0;
  }
}
</style>
