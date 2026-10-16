<script setup>
import { nextTick, ref, watch } from 'vue'
import { api } from '../api'
import { errorMessage } from '../api/client'
import { timeAgo } from '../utils/format'

const props = defineProps({
  taskId: { type: Number, required: true },
})

const comments = ref([])
const loading = ref(true)
const loadError = ref('')
const draft = ref('')
const posting = ref(false)
const postError = ref('')
const list = ref(null)

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    comments.value = await api.comments(props.taskId)
  } catch (e) {
    loadError.value = errorMessage(e, 'Could not load comments.')
  } finally {
    loading.value = false
  }
}

watch(() => props.taskId, load, { immediate: true })

// Called for our own posts and for live events; the id check stops the echo from doubling a comment.
function receive(comment) {
  if (comment.task_id !== props.taskId || comments.value.some((c) => c.id === comment.id)) return
  comments.value.push(comment)
  nextTick(() => list.value?.lastElementChild?.scrollIntoView({ block: 'nearest' }))
}

async function post() {
  const text = draft.value.trim()
  if (!text) {
    postError.value = 'Write something before posting.'
    return
  }
  posting.value = true
  postError.value = ''
  try {
    receive(await api.addComment(props.taskId, text))
    draft.value = ''
  } catch (e) {
    postError.value = errorMessage(e, 'Could not post the comment.')
  } finally {
    posting.value = false
  }
}

defineExpose({ receive })
</script>

<template>
  <section class="thread" aria-labelledby="comments-heading">
    <h3 id="comments-heading">Comments</h3>

    <p v-if="loading" class="muted">Loading comments</p>
    <p v-else-if="loadError" class="danger">
      {{ loadError }}
      <button type="button" class="btn" @click="load">Try again</button>
    </p>
    <p v-else-if="!comments.length" class="muted">No comments yet. Questions and progress notes for this task go here.</p>

    <ol v-else ref="list" class="comments">
      <li v-for="c in comments" :key="c.id">
        <p class="who">
          <span>{{ c.user?.name }}</span>
          <time class="muted" :datetime="c.created_at" :title="new Date(c.created_at).toLocaleString()">{{ timeAgo(c.created_at) }}</time>
        </p>
        <p class="body">{{ c.comment }}</p>
      </li>
    </ol>

    <form class="compose" @submit.prevent="post">
      <div class="field">
        <label for="comment-draft">Add a comment</label>
        <textarea
          id="comment-draft"
          v-model="draft"
          class="textarea"
          rows="3"
          maxlength="5000"
          :aria-invalid="Boolean(postError)"
          @keydown.ctrl.enter.prevent="post"
          @keydown.meta.enter.prevent="post"
        ></textarea>
      </div>
      <p v-if="postError" class="field-error" role="alert">{{ postError }}</p>
      <div class="compose-actions">
        <span class="muted hint">Ctrl + Enter posts</span>
        <button type="submit" class="btn" :disabled="posting">{{ posting ? 'Posting' : 'Post comment' }}</button>
      </div>
    </form>
  </section>
</template>

<style scoped>
.thread {
  display: grid;
  gap: var(--space-3);
}

.comments {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--space-3);
}

.comments li {
  padding-bottom: var(--space-3);
  border-bottom: 1px solid var(--line);
}

.who {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
  font-weight: 400;
}

.who time {
  font-weight: 300;
  font-size: var(--text-sm);
}

.body {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.compose {
  display: grid;
  gap: var(--space-2);
}

.compose-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-3);
}

.hint {
  font-size: var(--text-sm);
}

@media (pointer: coarse) {
  .hint {
    visibility: hidden;
  }
}
</style>
