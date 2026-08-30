import re
import glob

with open('f:/Degree-Library/style.css', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace .marquee-container CSS
old_marquee_css = r"""\.marquee-container\s*\{\s*display:\s*flex;\s*align-items:\s*center;\s*background-color:\s*#F06E44;.*?letter-spacing:\s*1px;\s*\}"""
new_marquee_css = """.marquee-container {
  display: flex;
  align-items: center;
  background-color: #F06E44;
  color: #000000;
  text-shadow: none;
  padding: 2px 0;
  font-family: Impact, 'Arial Black', sans-serif;
  font-size: 14px;
  font-weight: 900;
  text-transform: uppercase;
  border: none;
  margin-bottom: 0px;
  box-shadow: none;
  letter-spacing: 0.5px;
}"""

# Replace .blinking-tag CSS
old_blink_css = r"""\.blinking-tag\s*\{\s*font-family:\s*Impact.*?text-transform:\s*uppercase;\s*\}"""
new_blink_css = """.blinking-tag {
  font-family: Impact, 'Arial Black', sans-serif;
  font-size: 11px;
  background-color: #000000;
  color: #F06E44;
  padding: 2px 6px;
  margin-right: 10px;
  margin-left: 10px;
  flex-shrink: 0;
  animation: marqueeBlink 1s linear infinite;
  border: none;
  text-transform: uppercase;
}"""

c = re.sub(old_marquee_css, new_marquee_css, c, flags=re.DOTALL)
c = re.sub(old_blink_css, new_blink_css, c, flags=re.DOTALL)

# Also remove margin-top on #marqueeContainer globally if it exists, or in media query
c = re.sub(r'#marqueeContainer\s*\{[^}]*?margin-top:\s*10px;[^}]*?\}', r'#marqueeContainer {\n    border-radius: 12px;\n    margin-top: 0px;\n    margin-bottom: 10px;\n  }', c, flags=re.DOTALL)

# Also modify main padding-top in the mobile media query
c = re.sub(r'padding-top:\s*10px\s*!important;', r'padding-top: 0 !important;', c)

# Let's also check if there's any other global gap for main
# There might be margin-top: 20px on main or something
c = re.sub(r'main\s*\{\s*flex:\s*1;\s*width:\s*100%;\s*max-width:\s*900px;\s*margin:\s*40px\s*auto\s*0\s*auto;\s*padding:\s*0\s*20px;\s*\}', r'main { flex: 1; width: 100%; max-width: 900px; margin: 10px auto 0 auto; padding: 0 20px; }', c, flags=re.DOTALL)
# Or if it's margin-top: 20px
c = re.sub(r'margin:\s*20px\s*auto\s*0\s*auto;', r'margin: 0px auto 0 auto;', c)

with open('f:/Degree-Library/style.css', 'w', encoding='utf-8') as f:
    f.write(c)
print("Updated style.css")

# Bump cache version
html_files = glob.glob('f:/Degree-Library/*.html') + glob.glob('f:/Degree-Library/*.php')
for html_file in html_files:
    with open(html_file, 'r', encoding='utf-8') as f:
        html = f.read()
    
    html = re.sub(r'style\.min\.css\?v=[\d\.]+', 'style.min.css?v=6.5.0', html)
    html = re.sub(r'style\.css\?v=[\d\.]+', 'style.css?v=6.5.0', html)
    
    with open(html_file, 'w', encoding='utf-8') as f:
        f.write(html)
print("Bumped cache version to 6.5.0")
