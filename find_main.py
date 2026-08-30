import re

with open('f:/Degree-Library/style.css', 'r', encoding='utf-8') as f:
    c = f.read()

for i, line in enumerate(c.split('\n')):
    if 'main ' in line.lower() or 'main{' in line.lower():
        # Get next few lines
        block = "\n".join(c.split('\n')[i:i+6])
        if 'margin' in block:
            print(f"style.css line {i}:\n{block}\n")
