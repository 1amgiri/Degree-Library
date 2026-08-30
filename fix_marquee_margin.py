import re

with open('f:/Degree-Library/style.css', 'r', encoding='utf-8') as f:
    c = f.read()

# Let's fix the #marqueeContainer margin logic
c = re.sub(r'#marqueeContainer\s*\{\s*border-radius:\s*12px;\s*margin-top:\s*0px;\s*margin-bottom:\s*10px;\s*\}', r'#marqueeContainer {\n    border-radius: 12px;\n    margin: 0;\n  }\n  #marqueeContainer:not(:empty) {\n    margin-top: 10px;\n    margin-bottom: 10px;\n  }', c)

with open('f:/Degree-Library/style.css', 'w', encoding='utf-8') as f:
    f.write(c)

print("Updated style.css #marqueeContainer empty logic")
