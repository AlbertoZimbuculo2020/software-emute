import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Interceptar erros globalmente para tratar a expiração de sessão
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && (error.response.status === 401 || error.response.status === 419)) {
            // Sessão expirada, forçar um recarregamento para redirecionar para a página de Login
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
