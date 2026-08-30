import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if '$comment[\'name\']' in line or '$comment[\'is_admin\']' in line:
        print("Found PHP comment line:", line.strip()[:300])
