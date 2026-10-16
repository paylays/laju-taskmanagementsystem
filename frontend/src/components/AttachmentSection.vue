<script setup>
import { ref } from 'vue'
import { api } from '../api'
import { errorMessage } from '../api/client'
import { toast } from '../composables/useToasts'
import { auth } from '../stores/auth'
import { fileSize } from '../utils/format'
import { saveBlob } from '../utils/saveBlob'
import AttachmentThumb from './AttachmentThumb.vue'
import UploadZone from './UploadZone.vue'

const props = defineProps({
  task: { type: Object, required: true },
  attachments: { type: Array, required: true },
  canEdit: { type: Boolean, default: false },
})

const emit = defineEmits(['uploaded', 'removed'])

// Mirrors the server's largest limit so an oversized file fails before it is sent.
const MAX_BYTES = 50 * 1024 * 1024

const uploads = ref([])
const confirmingId = ref(null)
let nextKey = 1

async function upload(files) {
  for (const file of files) {
    const item = { key: nextKey++, name: file.name, progress: 0, error: '' }
    uploads.value.push(item)
    const entry = uploads.value.find((u) => u.key === item.key)

    if (file.size > MAX_BYTES) {
      entry.error = 'Larger than 50 MB, not uploaded.'
      continue
    }

    try {
      const attachment = await api.uploadAttachment(props.task.id, file, (p) => (entry.progress = p))
      uploads.value = uploads.value.filter((u) => u.key !== item.key)
      emit('uploaded', attachment)
      toast(`Uploaded ${file.name}. Scanning it before it can be downloaded.`)
    } catch (e) {
      entry.error = errorMessage(e, 'Upload failed.')
    }
  }
}

function dismissUpload(key) {
  uploads.value = uploads.value.filter((u) => u.key !== key)
}

async function download(attachment) {
  try {
    saveBlob(await api.attachmentBlob(attachment.id), attachment.file_name)
  } catch (e) {
    // Blob responses hide the JSON error body, so pick the message from the status.
    const status = e.response?.status
    toast(
      status === 409 ? 'Still scanning this file. Try again in a moment.'
        : status === 410 ? 'This file failed the virus scan and was removed.'
        : errorMessage(e, 'Download failed.'),
      'error',
    )
  }
}

async function remove(attachment) {
  if (confirmingId.value !== attachment.id) {
    confirmingId.value = attachment.id
    return
  }
  try {
    await api.deleteAttachment(attachment.id)
    emit('removed', attachment.id)
    toast(`Deleted ${attachment.file_name}.`, 'success')
  } catch (e) {
    toast(errorMessage(e, 'Could not delete the file.'), 'error')
  } finally {
    confirmingId.value = null
  }
}

const canRemove = (attachment) => auth.isAdmin || attachment.uploaded_by === auth.user?.id || props.task.created_by === auth.user?.id
</script>

<template>
  <section class="attachments" aria-labelledby="attachments-heading">
    <h3 id="attachments-heading">Files</h3>

    <UploadZone v-if="canEdit" @files="upload" />

    <ul v-if="uploads.length" class="uploads">
      <li v-for="u in uploads" :key="u.key" class="upload">
        <div class="upload-head">
          <span class="name">{{ u.name }}</span>
          <span v-if="!u.error">{{ Math.round(u.progress * 100) }}%</span>
          <button v-else type="button" class="btn btn-quiet" @click="dismissUpload(u.key)">Dismiss</button>
        </div>
        <p v-if="u.error" class="field-error" role="alert">{{ u.error }}</p>
        <div
          v-else
          class="bar"
          role="progressbar"
          :aria-label="`Uploading ${u.name}`"
          aria-valuemin="0"
          aria-valuemax="100"
          :aria-valuenow="Math.round(u.progress * 100)"
        >
          <span :style="{ width: `${u.progress * 100}%` }"></span>
        </div>
      </li>
    </ul>

    <p v-if="!attachments.length && !uploads.length" class="muted">
      No files yet.<template v-if="canEdit"> Drop screenshots, specs or recordings here so they stay with the task.</template>
    </p>

    <ul v-else class="list">
      <li v-for="a in attachments" :key="a.id" class="file">
        <AttachmentThumb :attachment="a" />
        <div class="info">
          <span class="name">{{ a.file_name }}</span>
          <span class="muted">
            {{ fileSize(a.file_size) }}
            <template v-if="a.scan_status === 'pending'">, scanning for viruses</template>
          </span>
          <span v-if="a.scan_status === 'infected'" class="danger">Failed the virus scan. The file was removed.</span>
        </div>
        <div class="actions">
          <button v-if="a.scan_status === 'clean'" type="button" class="btn" @click="download(a)">Download</button>
          <button
            v-if="canRemove(a)"
            type="button"
            class="btn btn-danger"
            :class="{ 'is-confirm': confirmingId === a.id }"
            @click="remove(a)"
            @blur="confirmingId === a.id && (confirmingId = null)"
          >
            {{ confirmingId === a.id ? 'Confirm delete' : 'Delete' }}
          </button>
        </div>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.attachments {
  display: grid;
  gap: var(--space-3);
}

.uploads,
.list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--space-2);
}

.upload-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-3);
}

.bar {
  height: 0.375rem;
  background: var(--line);
  margin-top: var(--space-1);
}

.bar span {
  display: block;
  height: 100%;
  background: var(--ink);
  transition: width 120ms linear;
}

.file {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
  padding-bottom: var(--space-2);
  border-bottom: 1px solid var(--line);
}

.info {
  flex: 1 1 10rem;
  min-width: 0;
  display: grid;
  font-size: var(--text-sm);
}

.name {
  font-size: var(--text);
  overflow-wrap: anywhere;
}

.actions {
  display: flex;
  gap: var(--space-2);
}
</style>
