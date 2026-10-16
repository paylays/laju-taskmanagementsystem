<script setup>
import { computed, defineAsyncComponent, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import { errorMessage } from '../api/client'
import FilterBar from '../components/FilterBar.vue'
import TaskRow from '../components/TaskRow.vue'
import { useLiveEvents } from '../composables/useLiveEvents'
import { toast } from '../composables/useToasts'
import { auth } from '../stores/auth'
import { STATUSES, plural, statusLabel } from '../utils/format'
import { saveBlob } from '../utils/saveBlob'

const props = defineProps({
  id: { type: String, default: null },
})

// The panel (form, uploads, comments) loads the first time a task is opened.
const TaskPanel = defineAsyncComponent(() => import('../components/TaskPanel.vue'))

const router = useRouter()
const PAGE = 25

const users = ref([])
const filters = ref({ search: '', assigned_user_id: '', priority: '', sort: '-created_at' })
const lanes = reactive(Object.fromEntries(STATUSES.map((s) => [s.value, { items: [], total: 0, page: 0, lastPage: 1, loading: true, error: '' }])))
const activeLane = ref('todo')
const selected = ref([])
const bulkStatus = ref('done')
const creating = ref(false)
const exporting = ref(false)
const panel = ref(null)

const openTaskId = computed(() => (props.id ? Number(props.id) : null))
const panelOpen = computed(() => creating.value || openTaskId.value !== null)
const isFiltered = computed(() => Boolean(filters.value.search || filters.value.assigned_user_id || filters.value.priority))

function queryParams() {
  const f = filters.value
  return Object.fromEntries(
    Object.entries({ search: f.search, assigned_user_id: f.assigned_user_id, priority: f.priority, sort: f.sort }).filter(([, v]) => v),
  )
}

/**
 * mode 'reset' shows the loading state, 'more' appends the next page, and 'refresh'
 * quietly refetches everything already on screen (used for live updates).
 */
const latestRequest = {}

async function loadLane(status, mode = 'reset') {
  const lane = lanes[status]
  // If filters change again before this response lands, the newer request wins.
  const requestId = (latestRequest[status] = (latestRequest[status] ?? 0) + 1)
  const isStale = () => requestId !== latestRequest[status]
  const perPage = mode === 'refresh' ? Math.min(100, Math.max(PAGE, lane.items.length)) : PAGE
  const page = mode === 'more' ? lane.page + 1 : 1

  if (mode === 'reset') {
    lane.loading = true
    lane.items = []
  }
  lane.error = ''

  try {
    const result = await api.listTasks({ ...queryParams(), status, per_page: perPage, page })
    if (isStale()) return
    lane.items = mode === 'more' ? [...lane.items, ...result.data] : result.data
    lane.total = result.meta.total
    lane.page = mode === 'refresh' ? Math.ceil(lane.items.length / PAGE) : result.meta.current_page
    lane.lastPage = Math.ceil(result.meta.total / PAGE)
  } catch (e) {
    if (!isStale()) lane.error = errorMessage(e, 'Could not load tasks.')
  } finally {
    if (!isStale()) lane.loading = false
  }
}

const loadAll = (mode = 'reset') => Promise.all(STATUSES.map((s) => loadLane(s.value, mode)))

watch(filters, () => {
  selected.value = []
  loadAll()
}, { deep: true })

onMounted(async () => {
  loadAll()
  try {
    users.value = await api.users()
  } catch (e) {
    toast(errorMessage(e, 'Could not load the team list.'), 'error')
  }
})

// Several events usually arrive together (a bulk update emits one per task), so batch the refetch.
let refreshTimer
function scheduleRefresh() {
  clearTimeout(refreshTimer)
  refreshTimer = setTimeout(() => loadAll('refresh'), 400)
}

const { status: liveStatus } = useLiveEvents((type, payload) => {
  panel.value?.applyEvent(type, payload)
  if (type !== 'attachment.updated') scheduleRefresh()
})

const liveText = computed(() => ({
  live: 'Live updates on',
  connecting: 'Connecting to live updates',
  reconnecting: 'Live updates paused, reconnecting',
})[liveStatus.value])

function openTask(id) {
  creating.value = false
  router.push({ name: 'task', params: { id } })
}

function startCreate() {
  router.push({ name: 'board' })
  creating.value = true
}

function closePanel() {
  creating.value = false
  if (openTaskId.value !== null) router.push({ name: 'board' })
}

function onCreated(task) {
  creating.value = false
  router.replace({ name: 'task', params: { id: task.id } })
}

function toggleSelected(id) {
  selected.value = selected.value.includes(id) ? selected.value.filter((x) => x !== id) : [...selected.value, id]
}

async function applyBulk() {
  try {
    const result = await api.bulkStatus(selected.value, bulkStatus.value)
    const skipped = result.skipped.length ? ` ${plural(result.skipped.length, 'task')} skipped: you can't change them.` : ''
    toast(`Moving ${plural(result.queued.length, 'task')} to ${statusLabel(bulkStatus.value)}. The board updates when the job finishes.${skipped}`)
    selected.value = []
  } catch (e) {
    toast(errorMessage(e, 'Could not start the bulk update.'), 'error')
  }
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

async function exportCsv() {
  exporting.value = true
  try {
    let job = await api.startExport(queryParams())
    const startedAt = Date.now()

    while (job.status === 'pending' || job.status === 'processing') {
      if (Date.now() - startedAt > 30_000) {
        toast('The export is still waiting in the queue. Check that the queue worker is running (php artisan queue:work).', 'error')
        return
      }
      await sleep(1000)
      job = await api.exportStatus(job.id)
    }

    if (job.status === 'failed') {
      toast('The export failed. Try again, or check the queue worker log.', 'error')
      return
    }

    saveBlob(await api.exportBlob(job.id), `lajur-tasks-${new Date().toISOString().slice(0, 10)}.csv`)
    toast(`Exported ${plural(job.row_count, 'task')} to CSV.`, 'success')
  } catch (e) {
    toast(errorMessage(e, 'Could not export tasks.'), 'error')
  } finally {
    exporting.value = false
  }
}

async function signOut() {
  try {
    await api.logout()
  } catch {
    // The token is dropped locally either way; a failed revoke only matters until it expires.
  }
  auth.clear()
  router.replace({ name: 'login' })
}

const emptyText = {
  todo: 'No open tasks. Use New task to add one.',
  in_progress: 'Nothing in progress. Open a task and set its status to In progress.',
  done: 'No finished tasks yet.',
}
</script>

<template>
  <div class="board-page">
    <header class="topbar">
      <p class="wordmark">Lajur</p>
      <p class="live" :class="`is-${liveStatus}`" role="status">
        <span class="marker" aria-hidden="true"></span>
        <span>{{ liveText }}</span>
      </p>
      <div class="account">
        <span class="who">{{ auth.user?.name }}<span v-if="auth.isAdmin" class="muted">, admin</span></span>
        <button type="button" class="btn btn-quiet" @click="signOut">Sign out</button>
      </div>
    </header>

    <main class="main">
      <div class="toolbar">
        <h1>Tasks</h1>
        <div class="toolbar-actions">
          <button type="button" class="btn" :disabled="exporting" @click="exportCsv">
            {{ exporting ? 'Exporting' : 'Export CSV' }}
          </button>
          <button type="button" class="btn btn-primary" @click="startCreate">New task</button>
        </div>
      </div>

      <FilterBar v-model="filters" :users="users" />

      <div v-if="selected.length" class="bulk" role="region" aria-label="Bulk actions">
        <span>{{ plural(selected.length, 'task') }} selected</span>
        <label class="visually-hidden" for="bulk-status">Move selected tasks to</label>
        <select id="bulk-status" v-model="bulkStatus" class="select">
          <option v-for="s in STATUSES" :key="s.value" :value="s.value">Move to {{ s.label }}</option>
        </select>
        <button type="button" class="btn" @click="applyBulk">Apply</button>
        <button type="button" class="btn btn-quiet" @click="selected = []">Clear selection</button>
      </div>

      <div class="lane-tabs" aria-label="Choose a lane">
        <button
          v-for="s in STATUSES"
          :key="s.value"
          type="button"
          class="lane-tab"
          :aria-pressed="activeLane === s.value"
          @click="activeLane = s.value"
        >
          {{ s.label }} <span class="count">{{ lanes[s.value].total }}</span>
        </button>
      </div>

      <div class="lanes">
        <section
          v-for="s in STATUSES"
          :key="s.value"
          class="lane"
          :class="{ 'is-active': activeLane === s.value }"
          :aria-labelledby="`lane-${s.value}`"
          :data-lane="s.value"
        >
          <h2 :id="`lane-${s.value}`" class="lane-head">
            {{ s.label }} <span class="count">{{ lanes[s.value].total }}</span>
          </h2>

          <p v-if="lanes[s.value].loading" class="state">Loading tasks</p>

          <div v-else-if="lanes[s.value].error" class="state" role="alert">
            <p class="danger">{{ lanes[s.value].error }}</p>
            <button type="button" class="btn" @click="loadLane(s.value)">Try again</button>
          </div>

          <p v-else-if="!lanes[s.value].items.length" class="state">
            {{ isFiltered ? 'No tasks in this lane match the filters.' : emptyText[s.value] }}
          </p>

          <template v-else>
            <ul class="rows">
              <TaskRow
                v-for="task in lanes[s.value].items"
                :key="task.id"
                :task="task"
                :selectable="auth.canEdit(task)"
                :selected="selected.includes(task.id)"
                @open="openTask"
                @toggle="toggleSelected"
              />
            </ul>
            <button
              v-if="lanes[s.value].items.length < lanes[s.value].total"
              type="button"
              class="btn btn-quiet more"
              @click="loadLane(s.value, 'more')"
            >
              Show more ({{ lanes[s.value].total - lanes[s.value].items.length }} left)
            </button>
          </template>
        </section>
      </div>
    </main>

    <TaskPanel
      v-if="panelOpen"
      ref="panel"
      :key="openTaskId ?? 'new'"
      :task-id="openTaskId"
      :users="users"
      @close="closePanel"
      @created="onCreated"
    />
  </div>
</template>

<style scoped>
.board-page {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.topbar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-2) var(--space-4);
  padding: var(--space-2) var(--space-4);
  padding-top: calc(var(--space-2) + env(safe-area-inset-top, 0px));
  /* Marking-yellow band (owner's request). Muted grey is only 3.4:1 on yellow, so all header text is ink. */
  background: var(--accent);
  color: var(--accent-ink);
  border-bottom: 1px solid var(--ink);
}

.topbar .muted {
  color: var(--accent-ink);
}

.topbar .btn-quiet:hover {
  background: var(--accent-hover);
}

.topbar .btn-quiet:active {
  background: var(--accent-active);
}

.wordmark {
  font-size: var(--text-md);
  font-weight: 400;
  letter-spacing: 0.02em;
}

.live {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

/* On the yellow band the marker is outlined while connecting and filled in ink once live. */
.marker {
  width: 0.5rem;
  height: 0.5rem;
  border: 1px solid var(--ink);
}

.live.is-live .marker {
  background: var(--ink);
}

.account {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-width: 0;
}

.who {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  padding: var(--space-4);
  padding-bottom: calc(var(--space-6) + env(safe-area-inset-bottom, 0px));
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.toolbar-actions {
  display: flex;
  gap: var(--space-2);
}

.bulk {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2) var(--space-3);
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--ink);
}

.bulk .select {
  width: auto;
}

.count {
  color: var(--muted);
  font-weight: 300;
}

/* Below 900px the lanes are too narrow side by side, so one lane shows at a time. */
.lane-tabs {
  display: flex;
  border-bottom: 1px solid var(--line);
  margin-bottom: calc(-1 * var(--space-4));
}

.lane-tab {
  flex: 1;
  min-height: var(--control-h);
  padding: 0 var(--space-2);
  border: 0;
  border-bottom: 4px solid transparent;
  background: transparent;
  color: var(--ink);
  font: inherit;
  font-weight: 400;
  cursor: pointer;
}

/* Yellow marking with an ink edge below it: the ink line carries the 3:1 contrast the yellow cannot. */
.lane-tab[aria-pressed='true'] {
  border-bottom-color: var(--accent);
  box-shadow: 0 1px 0 var(--ink);
}

.lane-tab[aria-pressed='true'] .count {
  color: var(--ink);
}

.lanes {
  flex: 1;
  display: grid;
}

.lane {
  display: none;
  background: var(--lane);
  min-width: 0;
}

.lane.is-active {
  display: block;
}

.lane-head {
  display: none;
}

.rows {
  list-style: none;
  margin: 0;
  padding: 0;
  border-top: 1px solid var(--line);
}

.more {
  width: 100%;
}

@media (max-width: 599px) {
  /* Keep name and Sign out next to the wordmark; the live status drops to its own line. */
  .live {
    order: 3;
    flex-basis: 100%;
  }
}

@media (max-width: 899px) {
  .rows {
    border-top: 0;
  }
}

@media (min-width: 900px) {
  .lane-tabs {
    display: none;
  }

  .lanes {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
    background: var(--lane);
  }

  .lane,
  .lane.is-active {
    display: block;
    min-height: 100%;
  }

  /* Identity motif: lanes divided like road markings. */
  .lane + .lane {
    border-left: 2px dashed var(--accent);
  }

  .lane-head {
    display: block;
    padding: var(--space-3) var(--space-3) var(--space-2) calc(2.75rem);
    font-size: var(--text);
  }
}
</style>
