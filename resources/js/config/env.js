const ENV = {
    API_URL: window.APP_URL || import.meta.env.VITE_HOST,
    DEMO: window.APP_DEMO || import.meta.env.VITE_DEMO,
    API_KEY: window.APP_KEY || import.meta.env.VITE_API_KEY
};
export default ENV;
