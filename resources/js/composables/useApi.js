export function useApi() {
    const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
    const APP_BASE_URL = import.meta.env.VITE_APP_URL || '';
    
    const getApiUrl = (path) => {
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;
        
        if (API_BASE_URL.endsWith('/api') && normalizedPath.startsWith('/api/')) {
            return `${API_BASE_URL}${normalizedPath.substring(4)}`;
        }
        
        if (normalizedPath.startsWith('/api/')) {
            return `${API_BASE_URL}${normalizedPath.substring(4)}`;
        }
        
        return `${API_BASE_URL}${normalizedPath}`;
    };

    const getAppUrl = (path) => {
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;
        return `${APP_BASE_URL}${normalizedPath}`;
    };

    const apiFetch = async (path, options = {}) => {
        const url = getApiUrl(path);
        
        const token = localStorage.getItem('token');
        
        const defaultHeaders = {};
        if (token) {
            defaultHeaders['Authorization'] = `Bearer ${token}`;
        }
        
        if (options.body && !options.headers?.['Content-Type']) {
            defaultHeaders['Content-Type'] = 'application/json';
        }
        
        const mergedOptions = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...(options.headers || {})
            }
        };
        
        const response = await fetch(url, mergedOptions);
        
        if (response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = getAppUrl('/login');
        }
        
        return response;
    };

    return {
        API_BASE_URL,
        APP_BASE_URL,
        getApiUrl,
        getAppUrl,
        apiFetch
    };
}
