/**
 * Global Search Implementation (Dropdown Style)
 */

const injectToggleStyles = () => {
  if (document.getElementById('globalSearchStyles')) return;
  const style = document.createElement('style');
  style.id = 'globalSearchStyles';
  style.textContent = `
    
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    /* Hide the input's original border when the gradient glow is active so they don't overlap */
    .global-search-wrapper.global-active-glow > input {
      border-color: transparent !important;
    }
  `;
  document.head.appendChild(style);
};

const initGlobalSearch = (inputId, currentPageType) => {
  injectToggleStyles();
  const input = document.getElementById(inputId);
  if (!input) return;

  let dropdown = document.getElementById('globalSearchDropdown');
  if (!dropdown) {
    dropdown = document.createElement('div');
    dropdown.id = 'globalSearchDropdown';
    dropdown.style.position = 'absolute';
    dropdown.style.display = 'none';
    dropdown.style.backgroundColor = '#ffffff';
    dropdown.style.border = '1px solid #cbd5e1';
    dropdown.style.borderTop = 'none';
    dropdown.style.borderRadius = '0 0 12px 12px';
    dropdown.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1)';
    dropdown.style.zIndex = '9999';
    dropdown.style.maxHeight = '350px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.boxSizing = 'border-box';
    dropdown.style.scrollbarWidth = 'none';
    
    document.body.appendChild(dropdown);
  }

  const updatePosition = () => {
    const rect = input.getBoundingClientRect();
    dropdown.style.top = `${rect.bottom + window.scrollY}px`;
    dropdown.style.left = `${rect.left + window.scrollX}px`;
    dropdown.style.width = `${rect.width}px`;
  };

  // Create a dedicated wrapper to hold the absolute toggle properly
  let wrapper = input.parentNode;
  if (!wrapper.classList.contains('global-search-wrapper')) {
    wrapper = document.createElement('div');
    wrapper.className = 'global-search-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.width = '100%';
    
    // Transfer margins from input to wrapper to prevent the gradient border from encapsulating margins
    const cs = window.getComputedStyle(input);
    wrapper.style.marginTop = cs.marginTop;
    wrapper.style.marginRight = cs.marginRight;
    wrapper.style.marginBottom = cs.marginBottom;
    wrapper.style.marginLeft = cs.marginLeft;
    
    input.style.setProperty('margin', '0', 'important');
    input.style.setProperty('width', '100%', 'important');
    input.style.setProperty('flex', '1', 'important');
    
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
  }

  // Extract input border radius to apply to the fluid gradient glow
  const inputRadius = window.getComputedStyle(input).borderRadius;
  wrapper.style.setProperty('--input-radius', inputRadius || '8px');

  // Force active glow on wrapper
  let debounceTimer;

  input.addEventListener('input', (e) => {
    const query = e.target.value.trim().toLowerCase();
    
    clearTimeout(debounceTimer);
    if (!query) {
      dropdown.style.display = 'none';
      dropdown.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(() => {
      performGlobalSearch(query, currentPageType, dropdown, updatePosition);
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });

  input.addEventListener('focus', () => {
    if (input.value.trim() && dropdown.innerHTML.trim() !== '') {
      updatePosition();
      dropdown.style.display = 'block';
    }
  });
  
  window.addEventListener('resize', () => {
    if (dropdown.style.display === 'block') {
      updatePosition();
    }
  });
};

const performGlobalSearch = async (query, currentPageType, dropdown, updatePosition) => {
  updatePosition();
  dropdown.style.display = 'block';
  dropdown.innerHTML = '<div style="padding: 12px; font-size: 14px; color: #64748b; text-align: center;">Searching other sections...</div>';

  const promises = [];

  const getUrl = (endpoint, q) => {
    if (typeof getProxiedUrl !== 'undefined') {
      return getProxiedUrl(endpoint, new URLSearchParams({ query: q }));
    }
    return `/${endpoint}?query=${encodeURIComponent(q)}`;
  };

  if (currentPageType !== 'materials' && currentPageType !== 'important') {
    promises.push(
      fetch(getUrl('materials.php', query))
        .then(res => res.json())
        .then(data => {
          const arr = Array.isArray(data) ? data : [];
          const filtered = arr.filter(item => 
            (item.name || '').toLowerCase().includes(query) ||
            (item.uploader || '').toLowerCase().includes(query) ||
            (item.category || '').toLowerCase().includes(query) ||
            (item.tags || '').toLowerCase().includes(query)
          );
          return { type: 'materials', data: filtered };
        })
        .catch(() => ({ type: 'materials', data: [] }))
    );
  }

  if (currentPageType !== 'community') {
    promises.push(
      fetch(getUrl('community_get.php', query))
        .then(res => res.json())
        .then(data => {
          const arr = Array.isArray(data) ? data : [];
          const filtered = arr.filter(item => {
            const cleanText = (item.content || '').replace(/<(style|script|svg)[^>]*>[\s\S]*?<\/\1>/gi, '').replace(/<[^>]*>?/gm, '').replace(/\s+/g, ' ').trim().toLowerCase();
            return cleanText.includes(query) || (item.user_name || '').toLowerCase().includes(query);
          });
          return { type: 'community', data: filtered };
        })
        .catch(() => ({ type: 'community', data: [] }))
    );
  }

  if (currentPageType !== 'mca') {
    promises.push(
      fetch('/mca.json')
        .then(res => res.json())
        .then(data => {
          const filtered = data.filter(item => 
            item.name.toLowerCase().includes(query) || 
            item.subject.toLowerCase().includes(query)
          );
          return { type: 'mca', data: filtered };
        })
        .catch(() => ({ type: 'mca', data: [] }))
    );
  }

  if (currentPageType !== 'icet') {
    promises.push(
      fetch('/icet.json')
        .then(res => res.json())
        .then(data => {
          const filtered = data.filter(p => 
            `${p.name} ${p.year} ${p.shift}`.toLowerCase().includes(query)
          );
          return { type: 'icet', data: filtered };
        })
        .catch(() => ({ type: 'icet', data: [] }))
    );
  }

  const results = await Promise.all(promises);
  renderGlobalResults(query, results, dropdown, updatePosition);
};

const renderGlobalResults = (query, results, dropdown, updatePosition) => {
  let html = '<div style="display: flex; flex-direction: column;">';
  let totalMatches = 0;

  const highlight = (text) => {
    if (!query || !text) return text;
    const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
    return String(text).replace(regex, '<strong style="color: #0f172a; background: transparent;">$1</strong>');
  };

  const itemStyle = "padding: 10px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s; text-decoration: none; color: #334155;";
  const hoverScript = "onmouseover=\"this.style.backgroundColor='#f8fafc'\" onmouseout=\"this.style.backgroundColor='transparent'\"";

  const getBadgeHtml = (text, bg, color) => `<span style="font-size: 10px; font-weight: bold; background: ${bg}; color: ${color}; padding: 2px 6px; border-radius: 4px; white-space: nowrap; margin-left: auto;">${text}</span>`;

  results.forEach(result => {
    if (result.data.length === 0) return;
    
    const maxItems = 5;
    const slice = result.data.slice(0, maxItems);
    totalMatches += result.data.length;

    if (result.type === 'materials') {
      slice.forEach(m => {
        const generatedSlug = m.name ? m.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '') : m.id;
        const slug = m.slug || generatedSlug;
        const name = m.name || '';
        html += `
          <a href="/material/${slug}" style="${itemStyle}" ${hoverScript}>
            <span style="font-size: 18px;">📄</span>
            <div style="display: flex; flex-direction: column; overflow: hidden;">
              <span style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${highlight(name)}</span>
              <span style="font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">by ${m.uploader || 'Anonymous'}</span>
            </div>
            ${getBadgeHtml('Material', '#e0e7ff', '#3730a3')}
          </a>
        `;
      });
    } 
    else if (result.type === 'community') {
      slice.forEach(p => {
        let cleanText = (p.content || '').replace(/<(style|script|svg)[^>]*>[\s\S]*?<\/\1>/gi, '').replace(/<[^>]*>?/gm, '').replace(/\s+/g, ' ').trim();
        
        let snippet = cleanText;
        if (snippet.length > 50) snippet = snippet.substring(0, 50) + '...';
        
        let genSlug = 'post';
        if (cleanText) {
            let text = cleanText.substring(0, 60);
            let lastSpace = text.lastIndexOf(' ');
            if (lastSpace > 0) text = text.substring(0, lastSpace);
            genSlug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '') || 'post';
        }
        const slug = p.slug || (genSlug + '-' + p.id);

        html += `
          <a href="/community/${slug}" style="${itemStyle}" ${hoverScript}>
            <span style="font-size: 18px;">💬</span>
            <div style="display: flex; flex-direction: column; overflow: hidden;">
              <span style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${highlight(snippet) || '<em>Image/Attachment</em>'}</span>
              <span style="font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.user_name || 'User'}</span>
            </div>
            ${getBadgeHtml('Post', '#fce7f3', '#9d174d')}
          </a>
        `;
      });
    }
    else if (result.type === 'mca') {
      slice.forEach(m => {
        html += `
          <a href="${m.file_path}" target="_blank" style="${itemStyle}" ${hoverScript}>
            <span style="font-size: 18px;">📁</span>
            <div style="display: flex; flex-direction: column; overflow: hidden;">
              <span style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${highlight(m.name)}</span>
              <span style="font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${m.semester} > ${m.subject}</span>
            </div>
            ${getBadgeHtml('MCA', '#dcfce7', '#166534')}
          </a>
        `;
      });
    }
    else if (result.type === 'icet') {
      slice.forEach(p => {
        const path = `ICET/${p.file}`;
        html += `
          <a href="${path}" target="_blank" style="${itemStyle}" ${hoverScript}>
            <span style="font-size: 18px;">📝</span>
            <div style="display: flex; flex-direction: column; overflow: hidden;">
              <span style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${highlight(p.name)}</span>
              <span style="font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.year} | ${p.shift}</span>
            </div>
            ${getBadgeHtml('ICET', '#fef3c7', '#92400e')}
          </a>
        `;
      });
    }
  });

  html += '</div>';

  if (totalMatches === 0) {
    html = '<div style="padding: 12px; font-size: 14px; color: #64748b; text-align: center;">No matches found in other sections.</div>';
  } else {
    html += `<div style="padding: 8px 16px; background: #f8fafc; font-size: 11px; color: #94a3b8; text-align: center; border-radius: 0 0 12px 12px;">${totalMatches > 20 ? 'Showing top results. ' : ''}Press enter to search native page.</div>`;
  }

  dropdown.innerHTML = html;
  updatePosition();
};

// Animated Placeholder
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('searchQuery');
  if (!input) return;
  
  const placeholders = [
    'Search "Materials"',
    'Search "Community Posts"',
    'Search "MCA Notes"',
    'Search "Important Notes"'
  ];
  
  let pIdx = 0;
  let isDeleting = false;
  let currentText = '';
  
  function typePlaceholder() {
    const fullText = placeholders[pIdx];
    
    if (isDeleting) {
      currentText = fullText.substring(0, currentText.length - 1);
    } else {
      currentText = fullText.substring(0, currentText.length + 1);
    }
    
    input.setAttribute('placeholder', currentText);
    
    let typeSpeed = isDeleting ? 30 : 60; // Fast typing
    
    if (!isDeleting && currentText === fullText) {
      typeSpeed = 1000; // Pause for 1 second when fully typed!
      isDeleting = true;
    } else if (isDeleting && currentText === '') {
      isDeleting = false;
      pIdx = (pIdx + 1) % placeholders.length;
      typeSpeed = 300;
    }
    
    setTimeout(typePlaceholder, typeSpeed);
  }
  
  setTimeout(typePlaceholder, 1000);
});
