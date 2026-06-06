import json

path = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(path, 'rb') as f:
    content = f.read()

# The file has 4 backslash bytes (\\\\) where it should have 2 (\\)
# Replace sequences of 4 backslashes before colon with 2
fixed = content.replace(b'\\\\\\\\:', b'\\\\:')

# Verify it's valid JSON
try:
    data = json.loads(fixed.decode('utf-8'))
    print('JSON valid! PSR-4 keys:', list(data['autoload']['psr-4'].keys()))
    with open(path, 'wb') as f:
        f.write(fixed)
    print('File updated.')
except Exception as e:
    print(f'Error: {e}')