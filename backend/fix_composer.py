import json

path = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(path, 'rb') as f:
    content = f.read()

text = content.decode('utf-8')
print("File size:", len(content))
print()

# Find psr-4 section
lines = text.split('\n')
for i, line in enumerate(lines):
    if 'psr-4' in line or 'App' in line or 'Database' in line:
        print(f"Line {i+1}: {repr(line)}")

print()
# Try to parse
try:
    data = json.loads(text)
    print("JSON parse: OK")
    print("autoload.psr-4:", data.get('autoload', {}).get('psr-4', {}))
except Exception as e:
    print("JSON parse FAIL:", e)

    # Find the problem area
    print()
    print("Checking psr-4 lines:")
    for i, line in enumerate(lines):
        if 'App' in line or 'Database' in line or 'Tests' in line:
            print(f"  Line {i+1}: {repr(line)}")
