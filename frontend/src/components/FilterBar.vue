<script setup>
import { computed, ref, watch } from 'vue'
import { PRIORITIES } from '../utils/format'
import { auth } from '../stores/auth'

const props = defineProps({
  modelValue: { type: Object, required: true },
  users: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const SORTS = [
  { value: '-created_at', label: 'Newest first' },
  { value: 'due_date', label: 'Due date, soonest first' },
  { value: '-priority', label: 'Priority, high first' },
  { value: '-updated_at', label: 'Recently changed' },
  { value: 'title', label: 'Title, A to Z' },
]

// Typing updates the box immediately; the board only refetches once typing pauses.
const search = ref(props.modelValue.search)
let searchTimer
watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => update({ search: value.trim() }), 300)
})
watch(() => props.modelValue.search, (value) => {
  if (value !== search.value.trim()) search.value = value
})

function update(patch) {
  emit('update:modelValue', { ...props.modelValue, ...patch })
}

const isFiltered = computed(() => Boolean(props.modelValue.search || props.modelValue.assigned_user_id || props.modelValue.priority))

function clear() {
  search.value = ''
  update({ search: '', assigned_user_id: '', priority: '' })
}
</script>

<template>
  <div class="filters" role="search">
    <div class="field search">
      <label for="filter-search">Search</label>
      <input id="filter-search" v-model="search" class="input" type="search" placeholder="Title or description" />
    </div>

    <div class="field">
      <label for="filter-assignee">Assigned to</label>
      <select
        id="filter-assignee"
        class="select"
        :value="modelValue.assigned_user_id"
        @change="update({ assigned_user_id: $event.target.value })"
      >
        <option value="">Anyone</option>
        <option v-if="auth.user" :value="String(auth.user.id)">Me</option>
        <option v-for="u in users.filter((u) => u.id !== auth.user?.id)" :key="u.id" :value="String(u.id)">
          {{ u.name }}
        </option>
      </select>
    </div>

    <div class="field">
      <label for="filter-priority">Priority</label>
      <select id="filter-priority" class="select" :value="modelValue.priority" @change="update({ priority: $event.target.value })">
        <option value="">Any</option>
        <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">{{ p.label }}</option>
      </select>
    </div>

    <div class="field">
      <label for="filter-sort">Sort</label>
      <select id="filter-sort" class="select" :value="modelValue.sort" @change="update({ sort: $event.target.value })">
        <option v-for="s in SORTS" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <button v-if="isFiltered" type="button" class="btn btn-quiet clear" @click="clear">Clear filters</button>
  </div>
</template>

<style scoped>
.filters {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr;
  align-items: end;
}

@media (min-width: 600px) {
  .filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .search {
    grid-column: 1 / -1;
  }
}

@media (min-width: 1100px) {
  .filters {
    grid-template-columns: minmax(14rem, 2fr) repeat(3, minmax(9rem, 1fr)) auto;
  }

  .search {
    grid-column: auto;
  }
}

.clear {
  justify-self: start;
}
</style>
