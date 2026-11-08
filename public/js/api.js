/**
 * public/js/api.js
 * ----------------
 * Tiny fetch wrapper for talking to the BloodLink API.
 * Every response looks like { ok, message?, ...data }.
 * Throws Error(message) on failure so pages can catch it.
 */
const APP_BASE = (() => {
  let path = (location.pathname || '/').replace(/\/+$/, '');
  const apiIdx = path.indexOf('/api');
  if (apiIdx >= 0) {
    return path.slice(0, apiIdx);
  }
  if (/\.php$/i.test(path)) {
    path = path.slice(0, path.lastIndexOf('/'));
  }
  if (path.endsWith('/public')) {
    path = path.slice(0, -7);
  }
  return path;
})();

const API = {
  url(path) {
    return path.startsWith('/') ? `${APP_BASE}${path}` : `${APP_BASE}/${path}`;
  },
  /* Read the CSRF token the server mirrored into a readable cookie. */
  csrf() {
    const match = document.cookie.match(/(?:^|;\s*)bl_csrf=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
  },
  async call(method, url, body) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json' }
    };
    // State-changing requests must echo the session's CSRF token back.
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method.toUpperCase())) {
      opts.headers['X-CSRF-Token'] = this.csrf();
    }
    if (body !== undefined) opts.body = JSON.stringify(body);
    const target = this.url(url);
    let res;
    try {
      res = await fetch(target, opts);
    } catch (e) {
      throw new Error('Cannot reach the server. Is BloodLink running?');
    }
    let data = null;
    try { data = await res.json(); } catch (e) { /* empty body */ }
    if (!res.ok || !data || data.ok === false) {
      // Only redirect to login on protected pages, NEVER on public pages like map.php or search.php!
      if (res.status === 401 && url === '/api/auth/me') {
        const publicPages = ['/map.php', '/search.php', '/index.php', '/download.php', '/login.php', '/register.php', '/forgot-password.php', '/verify-email.php'];
        const curPath = location.pathname.toLowerCase();
        const isPublic = publicPages.some(p => curPath.endsWith(p)) || curPath === '/' || curPath.endsWith('/public') || curPath.endsWith('/public/');
        if (!isPublic) {
          location.href = this.url('/login.php');
        }
      }
      throw new Error(data && data.message ? data.message : 'Something went wrong.');
    }
    return data;
  },
  get csrfToken() {
    return this.csrf();
  },
  get: (url) => API.call('GET', url),
  post: (url, body) => API.call('POST', url, body),
  put: (url, body) => API.call('PUT', url, body),
  del: (url) => API.call('DELETE', url),
  delete: (url, body) => API.call('DELETE', url, body)
};

