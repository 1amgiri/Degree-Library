import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'post-header' in line or 'p.name' in line or 'post[\'name\']' in line:
        print("Found in community_post.php:", line.strip()[:300])
