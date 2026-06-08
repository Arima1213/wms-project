const axios = require('axios');

async function test() {
  try {
    const login = await axios.post('http://localhost:8000/api/v1/login', {
      email: 'admin@wms.local',
      password: 'password123'
    });
    const token = login.data.token;
    
    try {
      const create = await axios.post('http://localhost:8000/api/v1/products', {
        code: 'PROD-TEST-999',
        sku: 'SKU-TEST-999',
        name: 'Produk Testing Playwright',
        product_type: 'standard',
        is_active: true
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      console.log('Success:', create.data);
    } catch (e) {
      console.log('Validation Error:', e.response.data);
    }
  } catch (e) {
    console.log('Login Error:', e.response?.data || e.message);
  }
}

test();
