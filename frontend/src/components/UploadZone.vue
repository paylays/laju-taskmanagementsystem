<script setup>
import { ref } from 'vue'

defineProps({
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['files'])

const input = ref(null)
const over = ref(false)
// dragenter/dragleave also fire for child elements; count them so the highlight doesn't flicker.
let depth = 0

function enter() {
  depth++
  over.value = true
}

function leave() {
  depth = Math.max(0, depth - 1)
  if (depth === 0) over.value = false
}

function drop(event) {
  depth = 0
  over.value = false
  const files = [...(event.dataTransfer?.files ?? [])]
  if (files.length) emit('files', files)
}

function pick(event) {
  const files = [...event.target.files]
  event.target.value = ''
  if (files.length) emit('files', files)
}
</script>

<template>
  <div
    class="zone"
    :class="{ 'is-over': over, 'is-disabled': disabled }"
    data-testid="upload-zone"
    @dragenter.prevent="!disabled && enter()"
    @dragover.prevent
    @dragleave.prevent="!disabled && leave()"
    @drop.prevent="!disabled && drop($event)"
  >
    <p>{{ over ? 'Release to upload' : 'Drag files here, or' }}</p>
    <button type="button" class="btn" :disabled="disabled" @click="input.click()">Choose files</button>
    <input ref="input" class="visually-hidden" type="file" multiple tabindex="-1" aria-hidden="true" @change="pick" />
    <p class="hint">Images up to 10 MB. PDF, Office, text and CSV up to 20 MB. MP4, WebM and MOV up to 50 MB.</p>
  </div>
</template>

<style scoped>
.zone {
  display: grid;
  justify-items: start;
  gap: var(--space-2);
  padding: var(--space-4);
  border: 1px dashed var(--control-border);
  background: var(--paper);
}

.zone.is-over {
  border: 1px solid var(--ink);
  background: var(--tint);
}

.zone.is-disabled {
  background: var(--lane);
}

.hint {
  font-size: var(--text-sm);
  color: var(--muted);
}
</style>
