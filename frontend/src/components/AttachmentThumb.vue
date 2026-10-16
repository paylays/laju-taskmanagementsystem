<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { api } from '../api'
import { fileExtension } from '../utils/format'

const props = defineProps({
  attachment: { type: Object, required: true },
})

const box = ref(null)
const src = ref(null)
const visible = ref(false)
let observer = null

// The thumbnail needs the bearer token, so it is fetched as a blob instead of a plain <img src>.
// Fetch only once the row scrolls into view, which is what loading="lazy" would do for a URL.
async function load() {
  if (!visible.value || !props.attachment.has_thumbnail || src.value) return
  try {
    src.value = URL.createObjectURL(await api.thumbnailBlob(props.attachment.id))
  } catch {
    src.value = null
  }
}

onMounted(() => {
  if (!('IntersectionObserver' in window)) {
    visible.value = true
    load()
    return
  }
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      visible.value = true
      observer.disconnect()
      load()
    }
  }, { rootMargin: '200px' })
  observer.observe(box.value)
})

// A file that finishes its virus scan while the panel is open gains a thumbnail.
watch(() => props.attachment.has_thumbnail, load)

onBeforeUnmount(() => {
  observer?.disconnect()
  if (src.value) URL.revokeObjectURL(src.value)
})
</script>

<template>
  <span ref="box" class="thumb-box">
    <img v-if="src" class="thumb" :src="src" alt="" width="48" height="48" decoding="async" />
    <span v-else class="thumb kind" aria-hidden="true">{{ fileExtension(attachment.file_name) }}</span>
  </span>
</template>

<style scoped>
.thumb-box {
  display: block;
  flex: 0 0 3rem;
}

.thumb {
  width: 3rem;
  height: 3rem;
  object-fit: cover;
  border: 1px solid var(--line);
}

.kind {
  display: grid;
  place-items: center;
  font-size: 0.6875rem;
  font-weight: 400;
  color: var(--muted);
  background: var(--lane);
}
</style>
