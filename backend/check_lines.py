p = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(p, 'rb') as f: data = f.read()

lines = data.decode('utf-8').split('\n')
for i, line in enumerate(lines):
    print(f"Line {i+1}: {repr(line)}")