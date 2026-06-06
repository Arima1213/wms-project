import json, re, shutil

path = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
bak = path + '.bak'
shutil.copy(path, bak)
print(f'Backup: {bak}')

with open(path, encoding='utf-8') as f:
    text = f.read()

# Fix: replace 4 backslashes before colon with 2 (the JSON-escaped single backslash)
# The pattern: namespace keys have "\\\\" (4 backslashes in source =2 in JSON value)
# They should have "\\" (2 backslashes in source = 1 in JSON value)
fixed = re.sub(r'(" (?:App|Database\Factories|Database\Seeders|Tests)\\\\)\\', lambda m: m.group(1).replace('\\\\', '\\') + ':', text)

try:
    data = json.loads(fixed)
    print('JSON valid after fix')
    with open(path, 'w', encoding='utf-8') as f:
        f.write(fixed)
    print('Written successfully')
except Exception as e:
    print(f'Fix failed: {e}')
