const http = require('http');
const data = JSON.stringify({ email: 'admin@wms.local', password: 'password123' });
const opts = {
  hostname: 'localhost',
  port: 5173,
  path: '/api/v1/login',
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(data),
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
};
const req = http.request(opts, r => {
  let d = '';
  r.on('data', c => d += c);
  r.on('end', () => console.log('LOGIN:', r.statusCode, d.slice(0, 500)));
});
req.on('error', e => console.error('FAIL:', e.message));
req.write(data);
req.end();
