const fs = require('fs');

const html = fs.readFileSync('mca.html', 'utf8');

// The directory starts at <ul id="mca-directory-tree">
const startIndex = html.indexOf('<ul id="mca-directory-tree"');
const endIndex = html.indexOf('</ul>', html.lastIndexOf('</ul>'));

let treeHtml = html.substring(startIndex, endIndex + 5);

// Let's use a simple regex-based extractor
// Find all <a> tags that look like files. They look like:
// 📄 <a href="..." ...>filename</a>
const fileRegex = /<a href="([^"]+)"[^>]*>([^<]+)<\/a>/g;

let files = [];
let match;
while ((match = fileRegex.exec(treeHtml)) !== null) {
  let url = match[1];
  let name = match[2].trim();
  
  // Extract path info to build category/tags
  // URL: MCA%20SVU%20-%20Notes/MCA%20SVU%20-%20Notes/Sem-1/ACCOUNTS/105A%20...
  let decodedUrl = decodeURIComponent(url);
  let parts = decodedUrl.split('/');
  
  let sem = '';
  let subject = '';
  
  // Try to find Sem-X and the subject (folder after Sem-X)
  for (let i = 0; i < parts.length; i++) {
    if (parts[i].toLowerCase().includes('sem-')) {
      sem = parts[i];
      if (i + 1 < parts.length) {
        subject = parts[i + 1];
        
        // Sometimes there's another subfolder like 'HAND WRITTEN NOTES'
        if (i + 2 < parts.length && parts[i + 2] !== name) {
          subject += ' - ' + parts[i + 2];
        }
      }
      break;
    }
  }
  
  files.push({
    name: name,
    file_path: url,
    semester: sem,
    subject: subject
  });
}

fs.writeFileSync('mca.json', JSON.stringify(files, null, 2));
console.log('Extracted ' + files.length + ' files to mca.json');
