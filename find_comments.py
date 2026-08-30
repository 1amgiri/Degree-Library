import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'renderComments' in line or 'innerHTML' in line and 'comment' in line:
        print("Found JS:", line.strip()[:300])
