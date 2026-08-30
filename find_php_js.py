import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'renderComments' in line or 'function renderComments' in line or 'let commentsHtml' in line:
        print("Found JS in community_post.php:", line.strip()[:1000])
