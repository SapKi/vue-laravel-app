const BASE = '/api/items';

export async function fetchItems(params = {}) {
    const query = new URLSearchParams(
        Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined))
    ).toString();
    const res = await axios.get(`${BASE}${query ? '?' + query : ''}`);
    return res.data;
}

export async function fetchItem(id) {
    const res = await axios.get(`${BASE}/${id}`);
    return res.data;
}

export async function submitItem(payload) {
    const res = await axios.post(BASE, payload);
    return res.data;
}

export async function reviewItem(id, payload) {
    const res = await axios.patch(`${BASE}/${id}/review`, payload);
    return res.data;
}

export async function deleteItem(id) {
    await axios.delete(`${BASE}/${id}`);
}
