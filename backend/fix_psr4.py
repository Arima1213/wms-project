p = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(p, 'rb') as f: data = f.read()

lines = data.decode('utf-8').split('\n')
print("BEFORE:")
for i in [32,33,34,39,40]:
    print(f"  Line {i+1}: {repr(lines[i])}")

# Fix: lines 33,34,35,40 have double backslash in namespace key
# Target: "App\": "app/", "Database\Factories\": "database/factories/", etc.
# We need to replace the last \\ before the colon with just \
# Pattern: "Namespace\\": -> "Namespace\": (remove one backslash before colon)
import re
fixed_lines = []
for i, line in enumerate(lines):
    # Fix psr-4 namespace lines: "Foo\\": -> "Foo\":  (remove one trailing backslash)
    line = re.sub(r'^(\s+"[A-Za-z\\]+)\\\(":\s+)', r'\1\2', line)
    fixed_lines.append(line)

text = '\n'.join(fixed_lines)
import json
try:
    data = json.loads(text)
    print("\nJSON valid after fix!")
    print("PSR-4 autoload:", list(data['autoload']['psr-4'].keys()))
    with open(p, 'w', encoding='utf-8') as f:
        f.write(text)
    print("File saved.")
except Exception as e:
    print(f"\nJSON still invalid: {e}")
    print("AFTER:")
    for i in [32,33,34,39,40]:
        print(f"  Line {i+1}: {repr(fixed_lines[i])}")
