<script setup>
import { computed } from 'vue'
import { dueLabel, plural, priorityLabel } from '../utils/format'

const props = defineProps({
  task: { type: Object, required: true },
  selectable: { type: Boolean, default: false },
  selected: { type: Boolean, default: false },
})

const emit = defineEmits(['open', 'toggle'])

const due = computed(() => (props.task.status === 'done' ? null : dueLabel(props.task.due_date)))

const counts = computed(() =>
  [
    props.task.comments_count ? plural(props.task.comments_count, 'comment') : null,
    props.task.attachments_count ? plural(props.task.attachments_count, 'file') : null,
  ].filter(Boolean).join(', '),
)
</script>

<template>
  <li class="row" :class="{ 'is-selected': selected }">
    <label v-if="selectable" class="pick">
      <input type="checkbox" :checked="selected" @change="emit('toggle', task.id)" />
      <span class="visually-hidden">Select "{{ task.title }}"</span>
    </label>
    <span v-else class="pick" aria-hidden="true"></span>

    <button type="button" class="open" @click="emit('open', task.id)">
      <span class="title">{{ task.title }}</span>
      <span class="meta">
        <span :class="['priority', `is-${task.priority}`]">{{ priorityLabel(task.priority) }}</span>
        <span>{{ task.assignee?.name ?? 'Unassigned' }}</span>
        <span v-if="due" :class="{ danger: due.overdue }">{{ due.text }}</span>
        <span v-if="counts">{{ counts }}</span>
      </span>
    </button>
  </li>
</template>

<style scoped>
.row {
  display: flex;
  align-items: stretch;
  border-bottom: 1px solid var(--line);
  background: var(--paper);
}

.row.is-selected {
  background: var(--tint);
}

.pick {
  display: grid;
  place-items: center;
  flex: 0 0 2.75rem;
  cursor: pointer;
}

.pick input {
  width: 1.125rem;
  height: 1.125rem;
  margin: 0;
  accent-color: var(--ink);
  cursor: pointer;
}

.open {
  flex: 1;
  min-width: 0;
  display: grid;
  gap: var(--space-1);
  padding: var(--space-3) var(--space-3) var(--space-3) 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.open:hover .title {
  text-decoration: underline;
  text-underline-offset: 3px;
}

.title {
  overflow-wrap: anywhere;
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-1) var(--space-3);
  font-size: var(--text-sm);
  color: var(--muted);
}

.priority {
  font-weight: 400;
}

.priority.is-high {
  color: var(--danger);
}

.priority.is-medium {
  color: var(--ink);
}
</style>
