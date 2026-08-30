import re

with open('f:/Degree-Library/style.css', 'r', encoding='utf-8') as f:
    c = f.read()

for i, line in enumerate(c.split('\n')):
    if 'keywords-wrapper' in line.lower() or 'marqueecontainer' in line.lower():
        block = "\n".join(c.split('\n')[max(0, i-1):i+5])
        print(f"style.css around line {i}:\n{block}\n")
