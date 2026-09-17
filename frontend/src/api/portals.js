import client from './client';

export async function fetchPortals(filters = {}) {
  const { data } = await client.get('/portals', { params: filters });
  return data; // { data, meta, summary }
}

export async function fetchPortal(id) {
  const { data } = await client.get(`/portals/${id}`);
  return data.data;
}

export async function performAction(id, payload) {
  const { data } = await client.post(`/portals/${id}/action`, payload);
  return data; // { data, log }
}

export async function seedDemo() {
  const { data } = await client.post('/portals/seed-demo');
  return data;
}
