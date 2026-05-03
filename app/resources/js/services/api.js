const defaultHeaders = {
  Accept: 'application/json',
  'Content-Type': 'application/json',
};

async function request(path, options = {}) {
  const response = await fetch(`/api${path}`, {
    headers: {
      ...defaultHeaders,
      ...(options.headers || {}),
    },
    ...options,
  });

  const contentType = response.headers.get('content-type') || '';
  const hasJsonResponse = contentType.includes('application/json');

  const data = hasJsonResponse ? await response.json() : null;

  if (!response.ok) {
    const error = new Error(data?.message || 'Erro ao processar requisição.');

    error.status = response.status;
    error.errors = data?.errors || {};

    throw error;
  }

  return data;
}

export const api = {
  get(path) {
    return request(path);
  },

  post(path, payload) {
    return request(path, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  put(path, payload) {
    return request(path, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  delete(path) {
    return request(path, {
      method: 'DELETE',
    });
  },
};