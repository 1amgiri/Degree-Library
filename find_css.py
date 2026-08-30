import re

with open('f:/Degree-Library/style.css', 'r', encoding='utf-8') as f:
    c = f.read()

for i, line in enumerate(c.split('\n')):
    if 'marquee' in line.lower():
        print(f"style.css line {i}: {line.strip()}")
