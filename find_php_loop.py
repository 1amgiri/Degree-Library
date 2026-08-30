import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'foreach' in line or 'foreach ($comments' in line or 'class="comment"' in line or 'class="reply"' in line:
        print("Found PHP loop:", line.strip()[:300])
