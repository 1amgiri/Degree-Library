const fs = require('fs');
let html = fs.readFileSync('mca.html', 'utf8');

const startTag = '<ul id="mca-directory-tree" style="list-style-type: none; padding-left: 0; text-align: left;">';
const endTagStr = '</ul>\n\n</div>  </main><footer class="footer">';

const startIndex = html.indexOf(startTag);
// Find the last </ul> before </div> </main>
const endRegionIndex = html.indexOf('</div>  </main>');
let endIndex = html.lastIndexOf('</ul>', endRegionIndex);
if (endIndex === -1) endIndex = endRegionIndex;

// We want to replace everything from startIndex to endIndex + 5 (</ul>)
const firstPart = html.substring(0, startIndex);
const newTree = `<ul id="mca-directory-tree" style="list-style-type: none; padding-left: 0; text-align: left;"></ul>`;
const lastPart = html.substring(endIndex + 5);

let newHtml = firstPart + newTree + lastPart;

// Now replace the script block
const scriptStart = newHtml.indexOf('<script>\ndocument.addEventListener(\'DOMContentLoaded\', () => {\n  const searchInput = document.getElementById(\'mcaSearch\');');
const scriptEnd = newHtml.indexOf('</script>\n</body></html>');

if (scriptStart !== -1 && scriptEnd !== -1) {
  const newScript = `<script>
document.addEventListener('DOMContentLoaded', async () => {
  const searchInput = document.getElementById('mcaSearch');
  const treeContainer = document.getElementById('mca-directory-tree');
  if (!searchInput || !treeContainer) return;
  
  let mcaFiles = [];
  try {
    const res = await fetch('/mca.json');
    mcaFiles = await res.json();
  } catch (err) {
    console.error('Failed to load MCA files', err);
    treeContainer.innerHTML = '<li>Error loading MCA files.</li>';
    return;
  }

  // Build tree data structure
  const treeData = {};
  mcaFiles.forEach(f => {
    if (!treeData[f.semester]) treeData[f.semester] = {};
    if (!treeData[f.semester][f.subject]) treeData[f.semester][f.subject] = [];
    treeData[f.semester][f.subject].push(f);
  });

  const renderTree = () => {
    let html = '';
    for (const [semester, subjects] of Object.entries(treeData)) {
      html += \`<li style="margin: 10px 0;">
        <details style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; cursor: pointer;">
          <summary style="font-weight: bold; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            📁 <span>\${semester}</span>
          </summary>
          <div style="margin-top: 10px; padding-left: 10px; border-left: 2px solid #cbd5e1;">
            <ul style="list-style-type: none; padding-left: 20px; text-align: left;">\`;
      
      for (const [subject, files] of Object.entries(subjects)) {
        html += \`<li style="margin: 10px 0;">
          <details style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; cursor: pointer;">
            <summary style="font-weight: bold; color: #1e293b; display: flex; align-items: center; gap: 8px;">
              📁 <span>\${subject}</span>
            </summary>
            <div style="margin-top: 10px; padding-left: 10px; border-left: 2px solid #cbd5e1;">
              <ul style="list-style-type: none; padding-left: 20px; text-align: left;">\`;
        
        files.forEach(f => {
          html += \`<li style="margin: 8px 0; padding-left: 10px; display: flex; align-items: center; gap: 8px;">
            📄 <a href="\${f.file_path}" target="_blank" style="color: #4f46e5; text-decoration: none; word-break: break-all;">\${f.name}</a>
          </li>\`;
        });
        
        html += \`</ul></div></details></li>\`;
      }
      html += \`</ul></div></details></li>\`;
    }
    treeContainer.innerHTML = html;
  };

  renderTree();

  searchInput.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase().trim();
    const tree = document.getElementById('mca-directory-tree');
    
    if (!term) {
      const items = tree.querySelectorAll('li');
      items.forEach(li => li.style.display = '');
      const details = tree.querySelectorAll('details');
      details.forEach(d => d.open = false);
      const marks = tree.querySelectorAll('mark');
      marks.forEach(m => {
        const parent = m.parentNode;
        parent.replaceChild(document.createTextNode(m.textContent), m);
        parent.normalize();
      });
      return;
    }

    const allItems = tree.querySelectorAll('li');
    allItems.forEach(li => li.style.display = 'none');
    
    // reset highlights
    const allLinks = tree.querySelectorAll('a');
    allLinks.forEach(link => {
      if (!link.dataset.originalText) link.dataset.originalText = link.textContent;
      link.innerHTML = link.dataset.originalText;
    });
    const allSummaries = tree.querySelectorAll('summary span');
    allSummaries.forEach(span => {
      if (!span.dataset.originalText) span.dataset.originalText = span.textContent;
      span.innerHTML = span.dataset.originalText;
    });

    const highlight = (text) => {
      const escapeRegExp = (string) => string.replace(/[.*+?^\${}()|[\\]\\\\]/g, '\\\\$&');
      const regex = new RegExp(\`(\${escapeRegExp(term)})\`, 'gi');
      return text.replace(regex, '<mark style="background-color: #fde047; color: #1e293b; padding: 0 2px; border-radius: 2px;">$1</mark>');
    };

    allLinks.forEach(link => {
      if (link.textContent.toLowerCase().includes(term)) {
        link.innerHTML = highlight(link.dataset.originalText);
        let parent = link.closest('li');
        if (parent) parent.style.display = '';
        while (parent && parent.id !== 'mca-directory-tree') {
          if (parent.tagName === 'LI') parent.style.display = '';
          if (parent.tagName === 'DETAILS') parent.open = true;
          parent = parent.parentElement;
        }
      }
    });

    allSummaries.forEach(span => {
      if (span.textContent.toLowerCase().includes(term)) {
        span.innerHTML = highlight(span.dataset.originalText);
        let details = span.closest('details');
        let parentLi = details.closest('li');
        if (parentLi) parentLi.style.display = '';
        details.open = true;
        
        const children = details.querySelectorAll('li');
        children.forEach(child => child.style.display = '');
        
        let parent = parentLi ? parentLi.parentElement : null;
        while (parent && parent.id !== 'mca-directory-tree') {
          if (parent.tagName === 'LI') parent.style.display = '';
          if (parent.tagName === 'DETAILS') parent.open = true;
          parent = parent.parentElement;
        }
      }
    });
  });
});
</script>
</body></html>`;

  newHtml = newHtml.substring(0, scriptStart) + newScript;
}

fs.writeFileSync('mca.html', newHtml);
console.log('Successfully updated mca.html');
