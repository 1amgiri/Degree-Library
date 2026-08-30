with open('f:/Degree-Library/app.js', 'r', encoding='utf-8') as f:
    c = f.read()

import re
match = re.search(r'const loadMarquee = async \(\) => \{.*?\n\};', c, flags=re.DOTALL)
if match:
    print(match.group(0))
