import re

with open('f:/Degree-Library/community.html', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'const renderPosts' in line:
        # Just grab the innerHTML string assignment
        match = re.search(r'innerHTML\s*=\s*`.*?`', line)
        if match:
            print(match.group(0)[:1500])
