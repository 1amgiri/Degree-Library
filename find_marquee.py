import re

# Check index.html
with open('f:/Degree-Library/index.html', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'marquee' in line.lower():
        print("index.html:", line[:500])

# Check app.js
with open('f:/Degree-Library/app.js', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'marquee' in line.lower():
        print("app.js:", line[:500])
