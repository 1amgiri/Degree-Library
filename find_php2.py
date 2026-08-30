import re

with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

for line in c.split('\n'):
    if 'post-header' in line:
        match = re.search(r'<div class="post-header".*?</div>', line)
        if match:
            print("Found post-header:", match.group(0))
