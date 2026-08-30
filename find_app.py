import re

with open('f:/Degree-Library/app.js', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'div class="post-header"' in line:
        print("Found in app.js:", line[:300])
