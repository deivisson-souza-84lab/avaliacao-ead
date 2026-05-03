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
    const fallbackMessage = response.status >= 500
      ? 'Banco de dados ainda não preparado. Execute as migrations conforme o README e atualize a página.'
      : 'Erro ao processar requisição.';

    const error = new Error(data?.message || fallbackMessage);

    if (response.status >= 500) {
      error.message = fallbackMessage;
    }

    error.status = response.status;
    error.errors = response.status >= 500 ? {} : (data?.errors || {});

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