import re
import glob

# SVG for empty profile icon
svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#94a3b8" style="margin-right: 8px; border-radius: 50%; vertical-align: middle;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>'

# 1. Update community.html
with open('f:/Degree-Library/community.html', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace in community.html
old_str_js = r'<a href="/community/\$\{postSlug\}" style="text-decoration: none; color: inherit; display: inline-flex; align-items: center;" title="View Post">\s*\$\{p\.is_admin \? `<span style="color: navy; font-weight: bold; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">\$\{p\.name\}</span>` : `<span>\$\{p\.name\}</span>`\}\s*</a>'
new_str_js = f'<a href="/community/${{postSlug}}" style="text-decoration: none; color: inherit; display: inline-flex; align-items: center;" title="View Post">{svg_icon}${{p.is_admin ? `<span style="color: navy; font-weight: bold; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">${{p.name}}</span>` : `<span>${{p.name}}</span>`}}</a>'

c = re.sub(old_str_js, new_str_js, c, flags=re.DOTALL)
with open('f:/Degree-Library/community.html', 'w', encoding='utf-8') as f:
    f.write(c)
print("Updated community.html")

# 2. Update community_post.php
with open('f:/Degree-Library/community_post.php', 'r', encoding='utf-8') as f:
    c = f.read()

old_str_php = r'<span style="font-weight: bold; color: #0f172a;"><\?php echo \$post\[\'is_admin\'\] \? \'<span style="color: navy; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">\' \. \$author \. \'</span>\' : \$author; \?></span>'
new_str_php = f'<span style="font-weight: bold; color: #0f172a; display: inline-flex; align-items: center;">{svg_icon}<?php echo $post[\'is_admin\'] ? \'<span style="color: navy; border-bottom: 1px dotted navy; display: inline-flex; align-items: center; gap: 4px;">\' . $author . \'</span>\' : $author; ?></span>'

c = re.sub(old_str_php, new_str_php, c, flags=re.DOTALL)
with open('f:/Degree-Library/community_post.php', 'w', encoding='utf-8') as f:
    f.write(c)
print("Updated community_post.php")

# Bump cache version
html_files = glob.glob('f:/Degree-Library/*.html') + glob.glob('f:/Degree-Library/*.php')
for html_file in html_files:
    with open(html_file, 'r', encoding='utf-8') as f:
        html = f.read()
    
    html = re.sub(r'style\.min\.css\?v=[\d\.]+', 'style.min.css?v=6.2.0', html)
    html = re.sub(r'style\.css\?v=[\d\.]+', 'style.css?v=6.2.0', html)
    
    with open(html_file, 'w', encoding='utf-8') as f:
        f.write(html)
print("Bumped cache version to 6.2.0")
