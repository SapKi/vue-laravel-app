export async function getCsrfCookie() {
    await axios.get('/sanctum/csrf-cookie');
}

export async function login(email, password) {
    await getCsrfCookie();
    const res = await axios.post('/api/login', { email, password });
    return res.data;
}

export async function register(name, email, password) {
    await getCsrfCookie();
    const res = await axios.post('/api/register', {
        name,
        email,
        password,
        password_confirmation: password,
    });
    return res.data;
}

export async function logout() {
    await axios.post('/api/logout');
}

export async function fetchMe() {
    const res = await axios.get('/api/me');
    return res.data;
}
