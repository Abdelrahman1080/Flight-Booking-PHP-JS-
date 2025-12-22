(function() {
  // Skip guard if explicitly disabled
  if (window.SKIP_AUTH_GUARD) return;

  const LOGIN_URL = '/Flight-Booking-V2/Front-end/html/login.html';
  const CHECK_URL = '/Flight-Booking-V2/Back-End/auth(log-register)/get-user.php';

  async function checkSession() {
    try {
      // First, check if we just registered (userData in sessionStorage from register.html)
      const storedUserData = sessionStorage.getItem('userData');
      if (storedUserData) {
        try {
          window.SkyWingsUser = JSON.parse(storedUserData);
          console.log('Loaded user from sessionStorage after registration');
          return;
        } catch(e) {
          console.warn('Failed to parse stored userData');
        }
      }

      // Otherwise, validate session with server
      const res = await fetch(CHECK_URL, { credentials: 'include' });
      if (!res.ok) throw new Error('Network error');
      const json = await res.json();
      if (!json || !json.success || !json.data || !json.data.user_id) {
        window.location.replace(LOGIN_URL);
        return;
      }
      // Expose user info for pages that want it
      window.SkyWingsUser = json.data;
    } catch (e) {
      // On any failure, go to login
      console.error('Session check failed:', e);
      window.location.replace(LOGIN_URL);
    }
  }

  // Run ASAP (before DOM ready)
  checkSession();
})();
