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
        return fetch(url, options);
    };

    return {
        API_BASE_URL,
        APP_BASE_URL,
        getApiUrl,
        getAppUrl,
        apiFetch
    };
}
