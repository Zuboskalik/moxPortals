import axios from 'axios';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const body = error.response?.data;

    if (status === 422) {
      return Promise.reject({
        kind: body?.error_code ? 'business_rule' : 'validation',
        errorCode: body?.error_code ?? null,
        message: body?.message ?? 'Действие невозможно.',
        errors: body?.errors ?? null,
        context: body?.context ?? null,
        status,
      });
    }

    if (status === 404) {
      return Promise.reject({
        kind: 'not_found',
        message: body?.message ?? 'Ресурс не найден.',
        status,
      });
    }

    if (status === 403) {
      return Promise.reject({
        kind: 'forbidden',
        message: body?.message ?? 'Действие запрещено в этом окружении.',
        status,
      });
    }

    return Promise.reject({
      kind: 'unknown',
      message: body?.message ?? error.message ?? 'Непредвиденная ошибка сервера.',
      status,
    });
  },
);

export default client;
