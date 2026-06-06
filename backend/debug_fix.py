p = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
with open(p, 'rb') as f: data = f.read()

# Show exact bytes around the problematic lines
lines = data.decode('utf-8').split('\n')
for i in [32,33,34,35,39,40]:
    b = lines[i].encode('utf-8')
    print(f"Line {i+1}: bytes={b.hex()} repr={repr(lines[i])}")

print()
# Replace: look for the 4-backslash pattern (\\\\) in bytes before colon
# "Namespace\\\\": -> "Namespace\": (remove one pair of backslashes)
import re

def fix_line(line):
    # Match: "Namespace\\": -> "Namespace\":  (remove trailing double-backslash)
    # In the string: we see 2 backslashes in a row, need to reduce to 1
    # Pattern: quote, namespace chars, TWO backslashes, quote-colon
    m = re.match(r'^(\s+"[A-Za-z ]+)\\\\\(":\s+)', line)
    if m:
        return m.group(1) + '\\' + m.group(2)
    return line

print("Testing fix_line:")
for i in [32,33,34,35,39,40]:
    orig = lines[i]
    fixed = fix_line(orig)
    status = "CHANGED" if orig != fixed else "same"
    print(f"  Line {i+1} [{status}]: {repr(fixed)}")
