document.addEventListener('DOMContentLoaded', function () {
    initMenuEditor('drawer_menu_json', 'drawer-menu-editor');
    initMenuEditor('footer_menu_json', 'footer-menu-editor');
    initOnboardingEditor('onboarding_steps_json', 'onboarding-steps-editor');

    // AJAX Saving Logic
    const form = document.querySelector('.alisha-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtns = document.querySelectorAll('input[type="submit"]');
            const originalTexts = [];

            submitBtns.forEach(btn => {
                originalTexts.push(btn.value);
                btn.value = 'Saving...';
                btn.disabled = true;
            });

            const formData = new FormData(form);
            formData.append('action', 'alisha_save_settings');
            formData.append('alisha_nonce', alishaAdmin.nonce);

            // alishaAdmin is localized in PHP
            fetch(alishaAdmin.ajax_url, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Settings saved successfully', 'success');
                    } else {
                        showToast(data.data || 'Error saving settings', 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error occurred', 'error');
                    console.error('Error:', error);
                })
                .finally(() => {
                    submitBtns.forEach((btn, index) => {
                        btn.value = originalTexts[index];
                        btn.disabled = false;
                    });
                });
        });
    }
});

function showToast(message, type = 'success') {
    // Remove existing toast
    const existing = document.querySelector('.alisha-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `alisha-toast ${type}`;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function initMenuEditor(inputId, containerId) {
    const input = document.getElementsByName(inputId)[0];
    const container = document.getElementById(containerId);

    if (!input || !container) return; // Exit if elements don't exist
    if (container.dataset.initialized === 'true') return;
    container.dataset.initialized = 'true';
    container.innerHTML = '';

    // Hide the original textarea
    input.style.display = 'none';

    let items = [];
    try {
        items = JSON.parse(input.value) || [];
    } catch (e) {
        items = [];
    }

    // Create UI Structure
    const table = document.createElement('div');
    table.className = 'menu-editor-table';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'shadcn-button shadcn-button-outline'; // Shadcn Style
    addButton.style.marginTop = '8px';
    addButton.innerHTML = '&#43; Add Menu Item'; // + Symbol
    addButton.onclick = () => {
        addItem(items, container, input);
    };

    container.appendChild(table);
    container.appendChild(addButton);

    renderItems(items, table, input, container);
}

function renderItems(items, container, input, rootContainer) {
    container.innerHTML = '';

    // Headers
    const headerRow = document.createElement('div');
    headerRow.className = 'menu-row header';
    headerRow.innerHTML = `
        <div class="col-drag"></div>
        <div class="col col-label">Label</div>
        <div class="col col-icon">Icon</div>
        <div class="col col-action-select">Action</div>
        <div class="col-wide">Value (Link / Page)</div>
        <div class="col-action"></div>
    `;
    container.appendChild(headerRow);

    if (items.length === 0) {
        const emptyRow = document.createElement('div');
        emptyRow.className = 'menu-row empty';
        emptyRow.innerHTML = '<div style="width:100%; text-align:center; padding: 20px;">No items. Click "Add Menu Item" below.</div>';
        container.appendChild(emptyRow);
        return;
    }

    items.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'menu-row';

        // Disable buttons based on position
        const upDisabled = index === 0 ? 'disabled' : '';
        const downDisabled = index === items.length - 1 ? 'disabled' : '';

        // Base Layout with specific classes for injection
        // Added shadcn-input class to inputs
        row.innerHTML = `
            <div class="col-drag">
                <button type="button" class="btn-move btn-up" ${upDisabled} title="Move Up">▲</button>
                <button type="button" class="btn-move btn-down" ${downDisabled} title="Move Down">▼</button>
            </div>
            <div class="col col-label"><input type="text" placeholder="Label" value="${escapeHtml(item.label || '')}" data-key="label" class="shadcn-input" style="border:none; box-shadow:none;"></div>
            <div class="col col-icon"></div>
            <div class="col col-action-select"></div>
            <div class="col-wide"><input type="text" placeholder="https://..." value="${escapeHtml(item.value || '')}" data-key="value" class="shadcn-input" style="border:none; box-shadow:none;"></div>
            <div class="col-action"><button type="button" class="button-link-delete" title="Delete">×</button></div>
        `;

        // 1. Icon Picker Logic (Global Modal Trigger)
        const iconContainer = document.createElement('div');
        iconContainer.className = 'icon-picker-container';
        iconContainer.style.height = '100%'; // Full height
        const currentIcon = item.icon || 'home';

        iconContainer.innerHTML = `
            <button type="button" class="icon-trigger">
                <span class="material-icons">${currentIcon}</span>
                <span class="icon-label">${currentIcon}</span>
            </button>
        `;

        const trigger = iconContainer.querySelector('.icon-trigger');
        trigger.onclick = (e) => {
            e.stopPropagation();
            openGlobalIconModal(item, index, items, container, input, rootContainer);
        };

        // Append to the specific column
        row.querySelector('.col-icon').appendChild(iconContainer);


        // 2. Action Select Logic
        const actionSelect = document.createElement('select');
        actionSelect.className = 'shadcn-select'; // Shadcn style
        actionSelect.style.border = 'none'; // Inside table, remove border
        actionSelect.style.boxShadow = 'none';

        actionSelect.innerHTML = `
            <option value="url" ${item.action === 'url' ? 'selected' : ''}>Open URL</option>
            <option value="external" ${item.action === 'external' ? 'selected' : ''}>External Link</option>
            <option value="share" ${item.action === 'share' ? 'selected' : ''}>Share App</option>
        `;
        actionSelect.value = item.action;
        actionSelect.onchange = (e) => {
            items[index].action = e.target.value;
            updateInput(items, input);
        };

        // Append to the specific column
        row.querySelector('.col-action-select').appendChild(actionSelect);


        // 3. Bind other inputs (Label, Value)
        row.querySelectorAll('input[type="text"]').forEach(el => {
            el.addEventListener('input', (e) => {
                items[index][e.target.getAttribute('data-key')] = e.target.value;
                updateInput(items, input);
            });
        });

        // 4. Delete
        row.querySelector('.button-link-delete').onclick = () => {
            if (confirm('Delete this item?')) {
                items.splice(index, 1);
                updateInput(items, input);
                renderItems(items, container, input, rootContainer);
            }
        };

        // 5. Move Up
        row.querySelector('.btn-up').onclick = () => {
            if (index > 0) {
                [items[index], items[index - 1]] = [items[index - 1], items[index]];
                updateInput(items, input);
                renderItems(items, container, input, rootContainer);
            }
        };

        // 6. Move Down
        row.querySelector('.btn-down').onclick = () => {
            if (index < items.length - 1) {
                [items[index], items[index + 1]] = [items[index + 1], items[index]];
                updateInput(items, input);
                renderItems(items, container, input, rootContainer);
            }
        };

        container.appendChild(row);
    });

    // Ensure global modal exists (singleton)
    if (!document.getElementById('alisha-icon-modal')) {
        createGlobalIconModal();
    }
}

// Global Modal Singleton
function createGlobalIconModal() {
    const modalOverlay = document.createElement('div');
    modalOverlay.id = 'alisha-icon-modal';
    modalOverlay.className = 'icon-modal-overlay';
    modalOverlay.style.display = 'none';

    // Structure: Overlay -> Modal -> Header/Grid
    modalOverlay.innerHTML = `
        <div class="icon-modal-content">
            <div class="icon-modal-header">
                <h4>Select Icon</h4>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="icon-grid">
                ${getAvailableIcons().map(icon => `
                    <div class="icon-option" data-icon="${icon}">
                        <span class="material-icons">${icon}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.body.appendChild(modalOverlay);

    // Close handlers
    modalOverlay.querySelector('.close-modal').onclick = () => closeModal();
    modalOverlay.onclick = (e) => {
        if (e.target === modalOverlay) closeModal();
    };
}

let activeCallback = null;

function openGlobalIconModal(item, index, items, container, input, rootContainer) {
    const modal = document.getElementById('alisha-icon-modal');
    if (!modal) return;

    modal.style.display = 'flex'; // Flex to center
    const options = modal.querySelectorAll('.icon-option');

    // Highlight current
    options.forEach(opt => {
        opt.classList.remove('selected');
        if (opt.getAttribute('data-icon') === item.icon) {
            opt.classList.add('selected');
        }

        // Rebind click
        opt.onclick = () => {
            items[index].icon = opt.getAttribute('data-icon');
            updateInput(items, input);
            renderItems(items, container, input, rootContainer); // Re-render table
            closeModal();
        };
    });
}

function closeModal() {
    const modal = document.getElementById('alisha-icon-modal');
    if (modal) modal.style.display = 'none';
}

function getAvailableIcons() {
    return [
        'home', 'info', 'contact_support', 'privacy_tip', 'list_alt',
        'share', 'settings', 'person', 'shopping_cart', 'favorite',
        'search', 'notifications', 'email', 'call', 'map',
        'lock', 'menu', 'arrow_forward', 'check', 'close'
    ];
}

function addItem(items, container, input) {
    items.push({ label: 'New Item', icon: 'home', action: 'url', value: '' });
    updateInput(items, input);
    // Find the table within the container to re-render
    const table = container.querySelector('.menu-editor-table');
    renderItems(items, table, input, container);
}

function updateInput(items, input) {
    input.value = JSON.stringify(items);
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* =========================================
   Onboarding Editor Logic
   ========================================= */

function initOnboardingEditor(inputId, containerId) {
    const input = document.getElementsByName(inputId)[0];
    const container = document.getElementById(containerId);

    if (!input || !container) return;

    // Hide original textarea
    input.style.display = 'none';

    let items = [];
    try {
        items = JSON.parse(input.value) || [];
    } catch (e) {
        items = [];
    }

    // Create UI
    const listContainer = document.createElement('div');
    listContainer.className = 'onboarding-list';
    listContainer.style.display = 'flex';
    listContainer.style.flexDirection = 'column';
    listContainer.style.gap = '16px';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'shadcn-button shadcn-button-outline';
    addButton.innerHTML = '&#43; Add Step';
    addButton.style.marginTop = '12px';
    addButton.onclick = () => {
        addOnboardingItem(items, listContainer, input);
    };

    container.appendChild(listContainer);
    container.appendChild(addButton);

    renderOnboardingItems(items, listContainer, input);
}

function renderOnboardingItems(items, container, input) {
    container.innerHTML = '';

    if (items.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:20px; color:hsl(var(--muted-foreground)); border: 1px dashed hsl(var(--border)); border-radius: var(--radius);">No steps defined. Add one below.</div>';
        return;
    }

    items.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'shadcn-card'; // Reuse card style for each step
        row.style.padding = '16px';
        row.style.position = 'relative';

        row.innerHTML = `
            <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                <h5 style="margin:0; font-size:13px; font-weight:600;">Step ${index + 1}</h5>
                <div>
                     <button type="button" class="btn-move btn-up" style="cursor:pointer; background:none; border:none;" title="Move Up">▲</button>
                     <button type="button" class="btn-move btn-down" style="cursor:pointer; background:none; border:none;" title="Move Down">▼</button>
                     <button type="button" class="btn-delete" style="cursor:pointer; background:none; border:none; color:red; margin-left:8px;" title="Delete">×</button>
                </div>
            </div>
            
            <div style="display:grid; gap: 12px;">
                <div>
                    <label style="display:block; font-size:12px; margin-bottom:4px;">Title</label>
                    <input type="text" class="shadcn-input" data-key="title" value="${escapeHtml(item.title || '')}" placeholder="Welcome">
                </div>
                <div>
                    <label style="display:block; font-size:12px; margin-bottom:4px;">Description</label>
                    <textarea class="shadcn-input" data-key="description" style="height:60px;" placeholder="Message...">${escapeHtml(item.description || '')}</textarea>
                </div>
                 <div>
                    <label style="display:block; font-size:12px; margin-bottom:4px;">Image URL</label>
                    <input type="text" class="shadcn-input" data-key="imageUrl" value="${escapeHtml(item.imageUrl || '')}" placeholder="https://...">
                </div>
                 <div>
                    <label style="display:block; font-size:12px; margin-bottom:4px;">Button Text</label>
                    <input type="text" class="shadcn-input" data-key="buttonText" value="${escapeHtml(item.buttonText || '')}" placeholder="Next">
                </div>
            </div>
        `;

        // Bind Inputs
        row.querySelectorAll('input, textarea').forEach(el => {
            el.addEventListener('input', (e) => {
                items[index][e.target.getAttribute('data-key')] = e.target.value;
                updateInput(items, input);
            });
        });

        // Bind Actions
        row.querySelector('.btn-up').onclick = () => {
            if (index > 0) {
                [items[index], items[index - 1]] = [items[index - 1], items[index]];
                updateInput(items, input);
                renderOnboardingItems(items, container, input);
            }
        };
        row.querySelector('.btn-down').onclick = () => {
            if (index < items.length - 1) {
                [items[index], items[index + 1]] = [items[index + 1], items[index]];
                updateInput(items, input);
                renderOnboardingItems(items, container, input);
            }
        };
        row.querySelector('.btn-delete').onclick = () => {
            if (confirm('Delete this step?')) {
                items.splice(index, 1);
                updateInput(items, input);
                renderOnboardingItems(items, container, input);
            }
        };

        container.appendChild(row);
    });
}

function addOnboardingItem(items, container, input) {
    items.push({
        id: 'step_' + Date.now(),
        title: '',
        description: '',
        imageUrl: '',
        buttonText: 'Next'
    });
    updateInput(items, input);
    renderOnboardingItems(items, container, input);
}
