// assets/js/theme.js
(() => {
  const KEY = 'theme';

  function getCookie(name) {
    const v = `; ${document.cookie}`;
    const parts = v.split(`; ${name}=`);
    return parts.length === 2 ? parts.pop().split(';').shift() : null;
  }

  function storeTheme(theme) {
    localStorage.setItem(KEY, theme);
    document.cookie = `${KEY}=${theme};path=/;max-age=${60*60*24*365}`;
  }

  function applyTheme(theme) {
    document.documentElement.classList.toggle('dark-theme', theme === 'dark');
    document.documentElement.classList.toggle('light-theme', theme === 'light');
    storeTheme(theme);

    // Update all theme toggle icons
    document.querySelectorAll('#themeToggle i').forEach(icon => {
      icon.classList.toggle('fa-moon', theme === 'dark');
      icon.classList.toggle('fa-sun', theme === 'light');
    });

    // Update Monaco Editor theme if it exists
    if (window.monaco) {
      const monacoTheme = theme === 'dark' ? 'vs-dark' : 'vs';
      monaco.editor.setTheme(monacoTheme);
    }
  }

  function initTheme() {
    const stored = localStorage.getItem(KEY) || getCookie(KEY);
    const prefers = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const initial = stored || prefers;

    // Apply theme immediately
    applyTheme(initial);

    // Initialize theme toggle buttons
    initializeThemeToggles();

    // Watch for dynamically added theme toggle buttons
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          initializeThemeToggles();
        }
      });
    });

    // Start observing the document with the configured parameters
    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    } else {
      document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { childList: true, subtree: true });
      });
    }
  }

  function initializeThemeToggles() {
    document.querySelectorAll('#themeToggle').forEach(btn => {
      // Only add event listener if it doesn't already have one
      if (!btn.dataset.themeInitialized) {
        btn.addEventListener('click', () => {
          const next = document.documentElement.classList.contains('dark-theme') ? 'light' : 'dark';
          applyTheme(next);
        });
        btn.dataset.themeInitialized = 'true';
      }
    });
  }

  // Initialize theme immediately
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
  } else {
    initTheme();
  }
})();
