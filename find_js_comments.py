import re

with open('f:/Degree-Library/community.html', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'div class="comment"' in line or 'div class="reply"' in line or 'comment.name' in line:
        print("Found JS in community.html:", line.strip()[:300])
