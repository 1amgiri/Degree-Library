import re

with open('f:/Degree-Library/index.html', 'r', encoding='utf-8') as f:
    c = f.read()

# find inline styles for marquee
matches = re.finditer(r'<style>.*?</style>', c, flags=re.DOTALL)
for m in matches:
    if 'marquee' in m.group(0):
        print(m.group(0)[:1500])
