import re

with open('f:/Degree-Library/community.html', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'const renderPosts' in line:
        print(line[:3000])
