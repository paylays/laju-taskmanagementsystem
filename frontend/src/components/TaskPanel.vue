<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api } from '../api'
import { errorMessage, fieldErrors } from '../api/client'
import { toast } from '../composables/useToasts'
import { auth } from '../stores/auth'
import { PRIORITIES, STATUSES, plural } from '../utils/format'
import AttachmentSection from './AttachmentSection.vue'
import CommentThread from './CommentThread.vue'

const props = defineProps({
  taskId: { type: Number, default: null },
  users: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'created'])

const dialog = ref(null)
const thread = ref(null)

const isNew = computed(() => props.taskId === null)
const task = ref(null)
const attachments = ref([])
const loading = ref(false)
const loadError = ref('')

const blank = () => ({ title: '', description: '', status: 'todo', priority: 'medium', assigned_user_id: '', due_date: '' })
const form = reactive(blank())
const snapshot = ref(JSON.stringify(blank()))
const dirty = computed(() => JSON.stringify(form) !== snapshot.value)

const errors = ref({})
const saving = ref(false)
const confirmingDelete = ref(false)
const changedElsewhere = ref(false)

const canEdit = computed(() => isNew.value || (task.value !== null && auth.canEdit(task.value)))
const canDelete = computed(() => task.value !== null && auth.canDelete(task.value))

function fill(source) {
  Object.assign(form, {
    title: source.title ?? '',
    description: source.description ?? '',
    status: source.status,
    priority: source.priority,
    assigned_user_id: source.assigned_user_id ? String(source.assigned_user_id) : '',
    due_date: source.due_date ?? '',
  })
  snapshot.value = JSON.stringify(form)
  changedElsewhere.value = false
}

async function load() {
  errors.value = {}
  confirmingDelete.value = false

  if (isNew.value) {
    task.value = null
    attachments.value = []
    fill(blank())
    return
  }

  loading.value = true
  loadError.value = ''
  try {
    task.value = await api.getTask(props.taskId)
    attachments.value = task.value.attachments ?? []
    fill(task.value)
  } catch (e) {
    loadError.value = e.response?.status === 404 ? 'This task no longer exists.' : errorMessage(e, 'Could not load the task.')
  } finally {
    loading.value = false
  }
}

watch(() => props.taskId, load, { immediate: true })
onMounted(() => dialog.value.showModal())

async function save() {
  if (!form.title.trim()) {
    errors.value = { title: ['Give the task a title.'] }
    return
  }

  saving.value = true
  errors.value = {}
  const payload = {
    ...form,
    title: form.title.trim(),
    description: form.description.trim() || null,
    assigned_user_id: form.assigned_user_id ? Number(form.assigned_user_id) : null,
    due_date: form.due_date || null,
  }

  try {
    if (isNew.value) {
      const created = await api.createTask(payload)
      toast(`Created "${created.title}". You can attach files now.`, 'success')
      emit('created', created)
    } else {
      task.value = { ...task.value, ...(await api.updateTask(props.taskId, payload)) }
      fill(task.value)
      toast('Changes saved.', 'success')
    }
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) toast(errorMessage(e, 'Could not save the task.'), 'error')
  } finally {
    saving.value = false
  }
}

async function remove() {
  if (!confirmingDelete.value) {
    confirmingDelete.value = true
    return
  }
  try {
    await api.deleteTask(props.taskId)
    toast(`Deleted "${task.value.title}".`, 'success')
    emit('close')
  } catch (e) {
    toast(errorMessage(e, 'Could not delete the task.'), 'error')
    confirmingDelete.value = false
  }
}

function upsertAttachment(attachment) {
  const index = attachments.value.findIndex((a) => a.id === attachment.id)
  if (index === -1) {
    attachments.value.unshift(attachment)
    return
  }
  if (attachments.value[index].scan_status === 'pending' && attachment.scan_status === 'infected') {
    toast(`${attachment.file_name} failed the virus scan and was removed.`, 'error')
  }
  attachments.value[index] = attachment
}

function removeAttachment(id) {
  attachments.value = attachments.value.filter((a) => a.id !== id)
}

// Live events from the board's SSE connection.
function applyEvent(type, payload) {
  const eventTaskId = payload.task_id ?? payload.task?.id
  if (isNew.value || eventTaskId !== props.taskId) return

  if (type === 'task.updated') {
    // Never overwrite what the user is typing; tell them instead.
    if (dirty.value) {
      changedElsewhere.value = true
    } else {
      task.value = { ...task.value, ...payload.task }
      fill(task.value)
    }
  } else if (type === 'task.deleted') {
    toast('Someone deleted this task.', 'error')
    emit('close')
  } else if (type === 'comment.created') {
    thread.value?.receive(payload.comment)
  } else if (type === 'attachment.created' || type === 'attachment.updated') {
    upsertAttachment(payload.attachment)
  } else if (type === 'attachment.deleted') {
    removeAttachment(payload.attachment_id)
  }
}

function reloadFromServer() {
  load()
}

defineExpose({ applyEvent })
</script>

<template>
  <dialog
    ref="dialog"
    class="panel"
    :aria-labelledby="'panel-title'"
    @cancel.prevent="emit('close')"
    @click.self="emit('close')"
  >
    <div class="sheet">
      <header class="head">
        <h2 id="panel-title">{{ isNew ? 'New task' : task ? `Task ${task.id}` : 'Task' }}</h2>
        <button type="button" class="btn btn-quiet" @click="emit('close')">Close</button>
      </header>

      <p v-if="loading" class="state">Loading task</p>

      <div v-else-if="loadError" class="state" role="alert">
        <p class="danger">{{ loadError }}</p>
        <button type="button" class="btn" @click="emit('close')">Back to the board</button>
      </div>

      <div v-else class="body">
        <p v-if="task" class="muted byline">
          Created by {{ task.creator?.name }} on
          {{ new Date(task.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
        </p>

        <p v-if="!canEdit" class="notice">
          Only the creator, the assignee, or an admin can change this task. You can still comment.
        </p>

        <p v-if="changedElsewhere" class="notice" role="status">
          Someone else changed this task while you were editing.
          <button type="button" class="btn" @click="reloadFromServer">Load their version</button>
        </p>

        <form class="form" novalidate @submit.prevent="save">
          <fieldset :disabled="!canEdit || saving">
            <legend class="visually-hidden">Task details</legend>

            <div class="field">
              <label for="task-title">Title</label>
              <input
                id="task-title"
                v-model="form.title"
                class="input"
                maxlength="255"
                :aria-invalid="Boolean(errors.title)"
                aria-describedby="task-title-error"
              />
              <p v-if="errors.title" id="task-title-error" class="field-error">{{ errors.title[0] }}</p>
            </div>

            <div class="field">
              <label for="task-description">Description</label>
              <textarea id="task-description" v-model="form.description" class="textarea" rows="4" maxlength="10000"></textarea>
            </div>

            <div class="grid">
              <div class="field">
                <label for="task-status">Status</label>
                <select id="task-status" v-model="form.status" class="select">
                  <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </div>

              <div class="field">
                <label for="task-priority">Priority</label>
                <select id="task-priority" v-model="form.priority" class="select">
                  <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
              </div>

              <div class="field">
                <label for="task-assignee">Assignee</label>
                <select id="task-assignee" v-model="form.assigned_user_id" class="select" :aria-invalid="Boolean(errors.assigned_user_id)">
                  <option value="">Unassigned</option>
                  <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                </select>
                <p v-if="errors.assigned_user_id" class="field-error">{{ errors.assigned_user_id[0] }}</p>
              </div>

              <div class="field">
                <label for="task-due">Due date</label>
                <input id="task-due" v-model="form.due_date" class="input" type="date" :aria-invalid="Boolean(errors.due_date)" />
                <p v-if="errors.due_date" class="field-error">{{ errors.due_date[0] }}</p>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="saving || (!isNew && !dirty)">
                {{ saving ? 'Saving' : isNew ? 'Create task' : 'Save changes' }}
              </button>
              <button
                v-if="canDelete"
                type="button"
                class="btn btn-danger"
                :class="{ 'is-confirm': confirmingDelete }"
                @click="remove"
              >
                {{ confirmingDelete ? 'Confirm delete' : 'Delete task' }}
              </button>
              <button v-if="confirmingDelete" type="button" class="btn btn-quiet" @click="confirmingDelete = false">Keep it</button>
            </div>
            <p v-if="confirmingDelete && attachments.length" class="danger">
              Deleting also removes {{ plural(attachments.length, 'file') }} and all comments.
            </p>
          </fieldset>
        </form>

        <template v-if="task">
          <AttachmentSection
            :task="task"
            :attachments="attachments"
            :can-edit="canEdit"
            @uploaded="upsertAttachment"
            @removed="removeAttachment"
          />
          <CommentThread ref="thread" :task-id="task.id" />
        </template>
      </div>
    </div>
  </dialog>
</template>

<style scoped>
.panel {
  margin: 0 0 0 auto;
  padding: 0;
  width: min(36rem, 100%);
  max-width: 100%;
  height: 100dvh;
  max-height: 100dvh;
  border: 0;
  border-left: 1px solid var(--ink);
  background: var(--paper);
  color: var(--ink);
}

.panel::backdrop {
  background: var(--backdrop);
}

.sheet {
  min-height: 100%;
  display: flex;
  flex-direction: column;
}

.head {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-2) var(--space-2) var(--space-4);
  padding-top: calc(var(--space-2) + env(safe-area-inset-top, 0px));
  background: var(--paper);
  border-bottom: 1px solid var(--line);
}

.body {
  display: grid;
  gap: var(--space-6);
  padding: var(--space-4);
  padding-bottom: calc(var(--space-6) + env(safe-area-inset-bottom, 0px));
}

.byline {
  font-size: var(--text-sm);
  margin-bottom: calc(-1 * var(--space-4));
}

.notice {
  padding: var(--space-3);
  background: var(--lane);
  border: 1px solid var(--line);
}

.notice .btn {
  margin-top: var(--space-2);
}

fieldset {
  margin: 0;
  padding: 0;
  border: 0;
  min-width: 0;
  display: grid;
  gap: var(--space-4);
}

.grid {
  display: grid;
  gap: var(--space-4);
  grid-template-columns: 1fr;
}

@media (min-width: 480px) {
  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}
</style>
