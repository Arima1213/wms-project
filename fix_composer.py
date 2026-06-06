import re
import json

path = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(path, 'rb') as f:
    content = f.read()

# Problem: file has single backslashes in PSR-4 keys (should be double-escaped for JSON)
# Fix: replace single backslash before letters with double backslash
# But NOT in valid JSON escapes like \n, \t, \r
# Strategy: find all lone backslashes followed by a letter and fix them
content_fixed = re.sub(b'(?<!\\\\)(?<!\\n)(?<!\\t)(?<!\\r)(\\\\\\w)', b'\\\\\\\\\\1', content)

with open(path, 'wb') as f:
    f.write(content_fixed)
print('Fixed!')

with open(path) as f:
    data = json.load(f)
print('JSON valid!')
print(json.dumps(data['autoload'], indent=2))