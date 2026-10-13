(function () {
  'use strict';

  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  if (!tokenMeta) return;

  const csrfToken = tokenMeta.content;
  const originalFetch = window.fetch.bind(window);
  const unsafeMethods = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

  function addTokenToForm(form) {
    if (!(form instanceof HTMLFormElement) || form.method.toUpperCase() !== 'POST') return;

    let field = form.querySelector('input[name="csrf_token"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = 'csrf_token';
      form.appendChild(field);
    }
    field.value = csrfToken;
  }

  document.querySelectorAll('form').forEach(addTokenToForm);
  document.addEventListener('submit', event => addTokenToForm(event.target), true);

  window.fetch = function (input, init = {}) {
    const requestUrl = new URL(
      input instanceof Request ? input.url : input,
      window.location.href
    );
    const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();

    if (requestUrl.origin !== window.location.origin || !unsafeMethods.has(method)) {
      return originalFetch(input, init);
    }

    const headers = new Headers(input instanceof Request ? input.headers : undefined);
    new Headers(init.headers || {}).forEach((value, name) => headers.set(name, value));
    headers.set('X-CSRF-Token', csrfToken);
    return originalFetch(input, { ...init, headers });
  };
})();
