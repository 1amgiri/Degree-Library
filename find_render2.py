import re

with open('f:/Degree-Library/community.html', 'r', encoding='utf-8') as f:
    c = f.read()

with open('f:/Degree-Library/find_render_out.txt', 'w', encoding='utf-8') as out:
    for line in c.split('\n'):
        if 'const renderPosts' in line:
            # We want to find how the author is rendered
            match = re.search(r'<div class="post-header">.*?</div>', line)
            if match:
                out.write(match.group(0))
            else:
                out.write("No post-header found. Writing whole string:\n")
                out.write(line)
