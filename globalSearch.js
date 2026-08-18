/**
 * Global Search Implementation (Dropdown Style)
 */

const injectToggleStyles = () => {
  if (document.getElementById('globalSearchStyles')) return;
  const style = document.createElement('style');
  style.id = 'globalSearchStyles';
  style.textContent = `
    .modern-toggle {
      position: absolute;
      left: 6px;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10;
    }
    .modern-toggle-checkbox {
      display: none;
    }
    .modern-toggle-label {
      display: flex;
      align-items: center;
      position: relative;
      width: 100px;
      height: 30px;
      background-color: #f1f5f9;
      border-radius: 20px;
      cursor: pointer;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
      padding: 2px;
      box-sizing: border-box;
    }
    .modern-toggle-inner {
      position: absolute;
      top: 2px;
      left: 2px;
      width: 48px;
      height: 26px;
      background: linear-gradient(135deg, #8b5cf6, #3b82f6);
      border-radius: 18px;
      transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .modern-toggle-checkbox:checked + .modern-toggle-label .modern-toggle-inner {
      transform: translateX(48px);
    }
    .modern-toggle-text {
      flex: 1;
      text-align: center;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      z-index: 1;
      transition: color 0.3s;
      user-select: none;
    }
    .modern-toggle-checkbox:not(:checked) + .modern-toggle-label .modern-toggle-text:first-of-type {
      color: white;
    }
    .modern-toggle-checkbox:not(:checked) + .modern-toggle-label .modern-toggle-text:last-of-type {
      color: #64748b;
    }
    .modern-toggle-checkbox:checked + .modern-toggle-label .modern-toggle-text:first-of-type {
      color: #64748b;
    }
    .modern-toggle-checkbox:checked + .modern-toggle-label .modern-toggle-text:last-of-type {
      color: white;
    }
    
    /* Fluid Gradient Border Animation */
    .global-search-wrapper.global-active-glow::after {
      content: "";
      position: absolute;
      inset: -2px;
      border-radius: var(--input-radius, 8px);
      padding: 4px; /* Increased from 2px for a thicker gradient line */
      background: linear-gradient(90deg, #ff007f, #7928ca, #00d2ff, #3a7bd5, #ff007f);
      background-size: 400% 400%;
      animation: fluid-gradient 3s ease infinite;
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
      z-index: 5;
    }
    
    @keyframes fluid-gradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    /* Hide the input's original border when the gradient glow is active so they don't overlap */
    .global-search-wrapper.global-active-glow > input {
      border-color: transparent !important;
    }
    
    .global-search-input-padding {
      padding-left: 115px !important;
    }

    @media (max-width: 450px) {
      .modern-toggle-label {
        width: 70px;
        height: 26px;
      }
      .modern-toggle-inner {
        width: 32px;
        height: 22px;
      }
      .modern-toggle-checkbox:checked + .modern-toggle-label .modern-toggle-inner {
        transform: translateX(34px);
      }
      .modern-toggle-text {
        font-size: 8px;
      }
      .global-search-input-padding {
        padding-left: 82px !important;
      }
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

  // Create Modern Toggle UI
  const toggleId = 'globalToggle_' + inputId;
  const toggleContainer = document.createElement('div');
  toggleContainer.className = 'modern-toggle';
  toggleContainer.innerHTML = `
    <input type="checkbox" id="${toggleId}" class="modern-toggle-checkbox" checked>
    <label for="${toggleId}" class="modern-toggle-label">
      <span class="modern-toggle-inner"></span>
      <span class="modern-toggle-text">Local</span>
      <span class="modern-toggle-text">Global</span>
    </label>
  `;
  wrapper.appendChild(toggleContainer);
  
  // Add padding to input so text doesn't overlap the toggle
  input.classList.add('global-search-input-padding');

  // Remove focus outline so it doesn't conflict with our beautiful animated gradient
  input.style.setProperty('outline', 'none', 'important');
  input.addEventListener('focus', () => {
      // The animated border acts as our focus indicator
  });

  const toggleInput = toggleContainer.querySelector('input');
  
  const updateGlow = () => {
    if (toggleInput.checked) {
      wrapper.classList.add('global-active-glow');
    } else {
      wrapper.classList.remove('global-active-glow');
    }
  };
  
  // Initial glow
  updateGlow();

  toggleInput.addEventListener('change', (e) => {
    updateGlow();
    if (!e.target.checked) {
      dropdown.style.display = 'none';
      dropdown.innerHTML = '';
    } else if (input.value.trim()) {
      input.dispatchEvent(new Event('input')); // Retrigger search
    }
  });

  let debounceTimer;

  input.addEventListener('input', (e) => {
    const query = e.target.value.trim().toLowerCase();
    
    clearTimeout(debounceTimer);
    if (!query || !toggleInput.checked) {
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
    if (input.value.trim() && dropdown.innerHTML.trim() !== '' && toggleInput.checked) {
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
