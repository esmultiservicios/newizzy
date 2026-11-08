/**
 * Pruebas de humo de API V1 (DEMO únicamente).
 * Variables: IZZY_TEST_BASE_URL, IZZY_TEST_USERNAME, IZZY_TEST_PASSWORD.
 * Nunca imprime tokens ni contraseñas. Crea y revoca una sesión de prueba.
 * Requiere Node 18+; no modifica facturas, clientes ni productos.
 */
const base = (process.env.IZZY_TEST_BASE_URL || '').replace(/\/$/, '');
const username = process.env.IZZY_TEST_USERNAME || '';
const password = process.env.IZZY_TEST_PASSWORD || '';
if (!base.startsWith('https://') || !username || !password) {
  throw new Error('Configure IZZY_TEST_BASE_URL (HTTPS), IZZY_TEST_USERNAME e IZZY_TEST_PASSWORD para DEMO.');
}
if (/sistema\.izzycloud\.app/i.test(base)) throw new Error('No ejecutar estas pruebas contra producción.');

async function call(path, method = 'GET', token, body) {
  const headers = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const response = await fetch(`${base}/api/v1/${path}`, {
    method, headers, body: body === undefined ? undefined : JSON.stringify(body), redirect: 'manual'
  });
  let json;
  try { json = await response.json(); } catch { throw new Error(`${path}: no devolvió JSON (HTTP ${response.status}).`); }
  return { status: response.status, json };
}
function verify(condition, label) {
  if (!condition) throw new Error(`FALLÓ: ${label}`);
  process.stdout.write(`OK: ${label}\n`);
}

let access, refresh;
try {
  let result = await call('auth/me.php');
  verify(result.status === 401, 'me sin Bearer denegado');
  result = await call('navigation/menus.php');
  verify(result.status === 401, 'menus sin Bearer denegado');
  result = await call('auth/login.php', 'POST', undefined, { usuario: username, password });
  verify(result.status === 200 && result.json.ok === true, 'login válido');
  access = result.json.data?.access_token;
  refresh = result.json.data?.refresh_token;
  verify(/^izzy_[a-f0-9]{64}$/.test(access || '') && /^izzyr_[a-f0-9]{64}$/.test(refresh || ''), 'formato seguro de tokens');
  result = await call('auth/me.php', 'GET', access);
  verify(result.status === 200 && !!result.json.data?.usuario?.id, 'me con sesión autorizada');
  result = await call('navigation/menus.php', 'GET', access);
  verify(result.status === 200 && Array.isArray(result.json.data?.menus), 'menús según plan y privilegio');
  result = await call('auth/refresh.php', 'POST', undefined, { refresh_token: refresh });
  verify(result.status === 200 && result.json.ok === true, 'refresh con rotación');
  const oldAccess = access, oldRefresh = refresh;
  access = result.json.data?.access_token;
  refresh = result.json.data?.refresh_token;
  result = await call('auth/me.php', 'GET', oldAccess);
  verify(result.status === 401, 'access anterior revocado tras refresh');
  result = await call('auth/logout.php', 'POST', access);
  verify(result.status === 200, 'logout válido');
  result = await call('auth/me.php', 'GET', access);
  verify(result.status === 401, 'access revocado tras logout');
  result = await call('auth/refresh.php', 'POST', undefined, { refresh_token: refresh });
  verify(result.status === 401, 'refresh revocado tras logout');
  process.stdout.write('Pruebas de humo completadas.\n');
} catch (error) {
  process.stderr.write(`${error.message}\n`);
  process.exitCode = 1;
} finally {
  // Si la prueba falla antes del logout, revocar el refresh de prueba.
  if (refresh) {
    try { await call('auth/logout.php', 'POST', undefined, { refresh_token: refresh }); } catch {}
  }
}
