import { http } from './client'

const data = (response) => response.data.data

export const api = {
  login: (email, password) => http.post('/auth/login', { email, password }).then((r) => r.data),
  logout: () => http.post('/auth/logout'),
  me: () => http.get('/auth/me').then(data),

  users: () => http.get('/users').then(data),

  listTasks: (params) => http.get('/tasks', { params }).then((r) => r.data),
  getTask: (id) => http.get(`/tasks/${id}`).then(data),
  createTask: (payload) => http.post('/tasks', payload).then(data),
  updateTask: (id, payload) => http.put(`/tasks/${id}`, payload).then(data),
  deleteTask: (id) => http.delete(`/tasks/${id}`),
  bulkStatus: (ids, status) => http.post('/tasks/bulk-status', { ids, status }).then((r) => r.data),

  comments: (taskId) => http.get(`/tasks/${taskId}/comments`).then(data),
  addComment: (taskId, comment) => http.post(`/tasks/${taskId}/comments`, { comment }).then(data),

  uploadAttachment(taskId, file, onProgress = () => {}) {
    const form = new FormData()
    form.append('file', file)
    return http
      .post(`/tasks/${taskId}/attachments`, form, {
        onUploadProgress: (event) => onProgress(event.total ? event.loaded / event.total : 0),
      })
      .then(data)
  },
  deleteAttachment: (id) => http.delete(`/attachments/${id}`),
  attachmentBlob: (id) => http.get(`/attachments/${id}/download`, { responseType: 'blob' }).then((r) => r.data),
  thumbnailBlob: (id) => http.get(`/attachments/${id}/thumbnail`, { responseType: 'blob' }).then((r) => r.data),

  startExport: (params) => http.post('/exports', params).then((r) => r.data),
  exportStatus: (id) => http.get(`/exports/${id}`).then((r) => r.data),
  exportBlob: (id) => http.get(`/exports/${id}/download`, { responseType: 'blob' }).then((r) => r.data),
}
