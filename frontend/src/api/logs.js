import client from './client';

export async function fetchLogs(filters = {}) {
  const { data } = await client.get('/logs', { params: filters });
  return data; // { data, meta }
}
