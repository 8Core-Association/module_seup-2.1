/**
 * Enhanced SEUP JavaScript functionality
 * Extends the modern design with specific module functionality
 */

// Enhanced form validation for SEUP specific fields
class SEUPFormValidator {
    constructor() {
        this.rules = {
            klasa_br: {
                pattern: /^\d{3}$/,
                message: 'Klasa broj mora imati točno 3 cifre'
            },
            sadrzaj: {
                pattern: /^\d{2}$/,
                message: 'Sadržaj mora imati točno 2 cifre'
            },
            dosje_br: {
                pattern: /^\d{2}$/,
                message: 'Dosje broj mora imati točno 2 cifre'
            },
            code_ustanova: {
                pattern: /^\d{4}-\d-\d$/,
                message: 'Format oznake ustanove mora biti YYYY-X-X'
            }
        };
    }

    validateField(field) {
        const fieldName = field.name || field.id;
        const rule = this.rules[fieldName];
        
        if (!rule) return true;
        
        if (field.value && !rule.pattern.test(field.value)) {
            this.showFieldError(field, rule.message);
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    }

    showFieldError(field, message) {
        field.style.borderColor = 'var(--seup-error)';
        field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        
        let errorDiv = field.parentNode.querySelector('.seup-field-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'seup-field-error';
            errorDiv.style.cssText = `
                color: var(--seup-error);
                font-size: 0.75rem;
                margin-top: var(--seup-space-1);
                display: flex;
                align-items: center;
                gap: var(--seup-space-1);
            `;
            field.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }

    clearFieldError(field) {
        field.style.borderColor = '';
        field.style.boxShadow = '';
        
        const errorDiv = field.parentNode.querySelector('.seup-field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

// Enhanced autocomplete functionality
class SEUPAutocomplete {
    constructor(input, resultsContainer, searchUrl) {
        this.input = input;
        this.resultsContainer = resultsContainer;
        this.searchUrl = searchUrl;
        this.debounceTimer = null;
        this.selectedIndex = -1;
        
        this.init();
    }

    init() {
        this.input.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });

        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.seup-dropdown')) {
                this.hideResults();
            }
        });
    }

    handleInput(value) {
        clearTimeout(this.debounceTimer);
        
        if (value.length < 1) {
            this.hideResults();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.search(value);
        }, 300);
    }

    handleKeydown(e) {
        const items = this.resultsContainer.querySelectorAll('.seup-dropdown-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection(items);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection(items);
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                    items[this.selectedIndex].click();
                }
                break;
            case 'Escape':
                this.hideResults();
                break;
        }
    }

    updateSelection(items) {
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
    }

    async search(term) {
        try {
            const response = await fetch(this.searchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `query=${encodeURIComponent(term)}`
            });

            if (!response.ok) throw new Error('Search failed');
            
            const results = await response.json();
            this.showResults(results);
        } catch (error) {
            console.error('Autocomplete error:', error);
        }
    }

    showResults(results) {
        this.selectedIndex = -1;
        this.resultsContainer.style.display = results.length > 0 ? 'block' : 'none';
        this.resultsContainer.innerHTML = '';

        results.forEach((result, index) => {
            const div = document.createElement('div');
            div.className = 'seup-dropdown-item';
            div.innerHTML = `
                <div style="font-weight: 500;">${result.klasa_br} - ${result.sadrzaj} - ${result.dosje_br}</div>
                <div style="font-size: 0.75rem; color: var(--seup-gray-500); margin-top: 2px;">
                    ${result.opis_klasifikacije ? result.opis_klasifikacije.substring(0, 50) + '...' : ''}
                </div>
            `;
            div.dataset.record = JSON.stringify(result);
            div.addEventListener('click', () => this.selectResult(result));
            this.resultsContainer.appendChild(div);
        });
    }

    selectResult(data) {
        // Trigger custom event for result selection
        const event = new CustomEvent('seup:autocomplete:select', {
            detail: data
        });
        this.input.dispatchEvent(event);
        this.hideResults();
    }

    hideResults() {
        this.resultsContainer.style.display = 'none';
        this.selectedIndex = -1;
    }
}

// Enhanced notification system
class SEUPNotifications {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'seup-notifications';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: var(--seup-space-2);
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `seup-alert seup-alert-${type} seup-fade-in`;
        notification.style.cssText = `
            display: flex;
            align-items: center;
            gap: var(--seup-space-2);
            padding: var(--seup-space-4);
            border-radius: var(--seup-radius);
            box-shadow: var(--seup-shadow-lg);
            cursor: pointer;
        `;

        const icon = this.getIcon(type);
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span style="flex: 1;">${message}</span>
            <button class="seup-tag-remove" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.container.appendChild(notification);

        // Auto-remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);

        // Remove on click
        notification.addEventListener('click', () => {
            notification.remove();
        });
    }

    getIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// Enhanced file upload with progress
class SEUPFileUpload {
    constructor(input, options = {}) {
        this.input = input;
        this.options = {
            maxSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
            ...options
        };
        
        this.init();
    }

    init() {
        this.input.addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files);
        });

        // Drag and drop support
        const dropZone = this.input.closest('.seup-upload-area');
        if (dropZone) {
            this.setupDragAndDrop(dropZone);
        }
    }

    setupDragAndDrop(dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            this.handleFileSelect(e.dataTransfer.files);
        });
    }

    handleFileSelect(files) {
        Array.from(files).forEach(file => {
            if (this.validateFile(file)) {
                this.uploadFile(file);
            }
        });
    }

    validateFile(file) {
        if (file.size > this.options.maxSize) {
            window.seupNotifications?.show('Datoteka je prevelika!', 'error');
            return false;
        }

        if (!this.options.allowedTypes.includes(file.type)) {
            window.seupNotifications?.show('Nevalja format datoteke!', 'error');
            return false;
        }

        return true;
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('document', file);
        formData.append('action', 'upload_document');
        
        // Add case ID if available
        const caseId = new URLSearchParams(window.location.search).get('id');
        if (caseId) {
            formData.append('case_id', caseId);
        }

        // Create progress indicator
        const progressContainer = this.createProgressIndicator(file.name);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                this.updateProgress(progressContainer, percentComplete);
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                window.seupNotifications?.show('Datoteka uspješno učitana!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.seupNotifications?.show('Greška pri učitavanju datoteke!', 'error');
            }
            progressContainer.remove();
        });

        xhr.addEventListener('error', () => {
            window.seupNotifications?.show('Greška pri učitavanju datoteke!', 'error');
            progressContainer.remove();
        });

        xhr.open('POST', window.location.href);
        xhr.send(formData);
    }

    createProgressIndicator(filename) {
        const container = document.createElement('div');
        container.className = 'seup-card seup-fade-in';
        container.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
        `;

        container.innerHTML = `
            <div class="seup-card-body">
                <div class="seup-flex seup-items-center seup-gap-2 seup-mb-2">
                    <i class="fas fa-upload seup-icon"></i>
                    <span style="flex: 1; font-weight: 500;">${filename}</span>
                </div>
                <div class="seup-progress">
                    <div class="seup-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="seup-text-small seup-mt-2">Učitavanje...</div>
            </div>
        `;

        document.body.appendChild(container);
        return container;
    }

    updateProgress(container, percent) {
        const progressBar = container.querySelector('.seup-progress-bar');
        const statusText = container.querySelector('.seup-text-small');
        
        progressBar.style.width = `${percent}%`;
        statusText.textContent = `${Math.round(percent)}% završeno`;
    }
}

// Initialize enhanced functionality when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize form validator
    window.seupValidator = new SEUPFormValidator();
    
    // Initialize notifications
    window.seupNotifications = new SEUPNotifications();
    
    // Setup enhanced form validation
    document.querySelectorAll('.seup-input, .seup-select, .seup-textarea').forEach(field => {
        field.addEventListener('blur', () => {
            window.seupValidator.validateField(field);
        });
        
        field.addEventListener('input', () => {
            // Clear errors on input
            window.seupValidator.clearFieldError(field);
        });
    });

    // Setup enhanced file upload
    document.querySelectorAll('input[type="file"]').forEach(input => {
        new SEUPFileUpload(input);
    });

    // Setup autocomplete for classification marks
    const klasaBrInput = document.getElementById('klasa_br');
    const autocompleteResults = document.getElementById('autocomplete-results');
    
    if (klasaBrInput && autocompleteResults) {
        const autocomplete = new SEUPAutocomplete(
            klasaBrInput, 
            autocompleteResults, 
            '../class/autocomplete.php'
        );
        
        // Handle result selection
        klasaBrInput.addEventListener('seup:autocomplete:select', (e) => {
            const data = e.detail;
            
            // Populate form fields
            document.getElementById('sadrzaj').value = data.sadrzaj || '';
            document.getElementById('dosje_br').value = data.dosje_br || '';
            
            const vrijemeCuvanja = document.getElementById('vrijeme_cuvanja');
            if (vrijemeCuvanja) {
                vrijemeCuvanja.value = data.vrijeme_cuvanja === '0' ? 'permanent' : data.vrijeme_cuvanja;
            }
            
            const opisKlasifikacije = document.getElementById('opis_klasifikacije');
            if (opisKlasifikacije) {
                opisKlasifikacije.value = data.opis_klasifikacije || '';
            }
            
            // Store ID for updates
            const hiddenId = document.getElementById('hidden_id_klasifikacijske_oznake');
            if (hiddenId) {
                hiddenId.value = data.ID;
            }
            
            // Show success feedback
            window.seupNotifications?.show('Podaci uspješno učitani', 'success', 2000);
        });
    }

    // Enhanced button interactions
    document.querySelectorAll('.seup-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Add loading state for form submissions
            if (this.type === 'submit') {
                window.seupModern?.showLoading(this);
                
                // Remove loading state after form submission
                setTimeout(() => {
                    window.seupModern?.hideLoading(this);
                }, 2000);
            }
        });
    });

    // Setup modern tab functionality
    setupModernTabs();
    
    // Setup enhanced dropdowns
    setupEnhancedDropdowns();
});

function setupModernTabs() {
    const tabs = document.querySelectorAll('.seup-nav-tab');
    const tabPanes = document.querySelectorAll('.seup-tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.style.display = 'none';
                pane.classList.remove('active');
            });
            
            // Add active class to clicked tab and corresponding pane
            tab.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.style.display = 'block';
                targetPane.classList.add('active', 'seup-fade-in');
            }
        });
    });
}

function setupEnhancedDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            
            const menuId = trigger.getAttribute('data-dropdown');
            const menu = document.getElementById(menuId);
            
            if (menu) {
                const isVisible = menu.style.display !== 'none';
                
                // Hide all other dropdowns
                document.querySelectorAll('.seup-dropdown-menu').forEach(m => {
                    m.style.display = 'none';
                });
                
                // Toggle current dropdown
                menu.style.display = isVisible ? 'none' : 'block';
                
                if (!isVisible) {
                    menu.classList.add('seup-fade-in');
                }
            }
        });
    });
}

// Enhanced search functionality
function setupEnhancedSearch() {
    const searchInputs = document.querySelectorAll('[data-search]');
    
    searchInputs.forEach(input => {
        const searchTarget = input.getAttribute('data-search');
        const targetElements = document.querySelectorAll(searchTarget);
        
        input.addEventListener('input', window.seupModern?.debounce((e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            targetElements.forEach(element => {
                const text = element.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                element.style.display = matches ? '' : 'none';
                
                if (matches) {
                    element.classList.add('seup-fade-in');
                }
            });
        }, 300));
    });
}

// Export for global use
window.SEUPFormValidator = SEUPFormValidator;
window.SEUPAutocomplete = SEUPAutocomplete;
window.SEUPNotifications = SEUPNotifications;
window.SEUPFileUpload = SEUPFileUpload;

// Tags Modal Functionality
class SEUPTagsModal {
    constructor() {
        this.selectedTags = new Set();
        this.availableTags = [];
        this.init();
    }

    init() {
        this.createModal();
        this.setupEventListeners();
        this.loadAvailableTags();
    }

    createModal() {
        const modalHTML = `
            <div id="seupTagsModal" class="seup-tags-modal">
                <div class="seup-tags-modal-content">
                    <div class="seup-tags-modal-header">
                        <h3 class="seup-tags-modal-title">
                            <i class="fas fa-tags"></i>
                            Odaberi Oznake
                        </h3>
                        <button class="seup-tags-modal-close" onclick="window.seupTagsModal.close()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="seup-tags-modal-body">
                        <div class="seup-tags-search">
                            <div style="position: relative;">
                                <input type="text" class="seup-input" placeholder="Pretraži oznake..." id="tagsSearchInput">
                                <i class="fas fa-search seup-tags-search-icon"></i>
                            </div>
                        </div>
                        <div id="tagsGridContainer" class="seup-tags-grid-modal">
                            <!-- Tags will be loaded here -->
                        </div>
                    </div>
                    <div class="seup-tags-modal-footer">
                        <div class="seup-tags-count">
                            <i class="fas fa-check-circle"></i>
                            <span id="selectedCount">0 odabrano</span>
                        </div>
                        <div class="seup-flex seup-gap-2">
                            <button class="seup-btn seup-btn-secondary" onclick="window.seupTagsModal.close()">
                                <i class="fas fa-times"></i> Odustani
                            </button>
                            <button class="seup-btn seup-btn-primary" onclick="window.seupTagsModal.confirm()">
                                <i class="fas fa-check"></i> Potvrdi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('tagsSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterTags(e.target.value);
            });
        }

        // Close modal on overlay click
        const modal = document.getElementById('seupTagsModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close();
                }
            });
        }

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                this.close();
            }
        });
    }

    async loadAvailableTags() {
        try {
            // Simulate loading tags - replace with actual AJAX call
            this.availableTags = [
                { id: 1, name: 'Hitno', color: 'red' },
                { id: 2, name: 'Važno', color: 'orange' },
                { id: 3, name: 'Interno', color: 'blue' },
                { id: 4, name: 'Javno', color: 'green' },
                { id: 5, name: 'Povjerljivo', color: 'purple' },
                { id: 6, name: 'Arhiva', color: 'gray' }
            ];
            
            this.renderTags();
        } catch (error) {
            console.error('Error loading tags:', error);
        }
    }

    renderTags() {
        const container = document.getElementById('tagsGridContainer');
        if (!container) return;

        container.innerHTML = '';

        this.availableTags.forEach(tag => {
            const tagElement = document.createElement('div');
            tagElement.className = 'seup-tag-option-modal';
            tagElement.dataset.tagId = tag.id;
            tagElement.innerHTML = `
                <i class="fas fa-tag" style="color: var(--seup-${tag.color}, #3b82f6);"></i>
                ${tag.name}
            `;
            
            if (this.selectedTags.has(tag.id)) {
                tagElement.classList.add('selected');
            }
            
            tagElement.addEventListener('click', () => {
                this.toggleTag(tag.id, tagElement);
            });
            
            container.appendChild(tagElement);
        });
    }

    toggleTag(tagId, element) {
        if (this.selectedTags.has(tagId)) {
            this.selectedTags.delete(tagId);
            element.classList.remove('selected');
        } else {
            this.selectedTags.add(tagId);
            element.classList.add('selected');
        }
        
        this.updateSelectedCount();
    }

    updateSelectedCount() {
        const countElement = document.getElementById('selectedCount');
        if (countElement) {
            const count = this.selectedTags.size;
            countElement.textContent = `${count} odabrano`;
        }
    }

    filterTags(searchTerm) {
        const tagElements = document.querySelectorAll('.seup-tag-option-modal');
        const term = searchTerm.toLowerCase();
        
        tagElements.forEach(element => {
            const tagName = element.textContent.toLowerCase();
            if (tagName.includes(term)) {
                element.style.display = 'flex';
            } else {
                element.style.display = 'none';
            }
        });
    }

    open() {
        const modal = document.getElementById('seupTagsModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Focus search input
            setTimeout(() => {
                const searchInput = document.getElementById('tagsSearchInput');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 100);
        }
    }

    close() {
        const modal = document.getElementById('seupTagsModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    confirm() {
        // Update the display area with selected tags
        this.updateSelectedTagsDisplay();
        
        // Update hidden form inputs
        this.updateFormInputs();
        
        // Close modal
        this.close();
        
        // Show success message
        if (window.seupNotifications) {
            const count = this.selectedTags.size;
            window.seupNotifications.show(`Odabrano ${count} oznaka`, 'success', 3000);
        }
    }

    updateSelectedTagsDisplay() {
        const displayArea = document.getElementById('selectedTagsDisplay');
        if (!displayArea) return;

        displayArea.innerHTML = '';
        
        if (this.selectedTags.size === 0) {
            displayArea.classList.add('empty');
            return;
        }
        
        displayArea.classList.remove('empty');
        
        this.selectedTags.forEach(tagId => {
            const tag = this.availableTags.find(t => t.id === tagId);
            if (tag) {
                const tagElement = document.createElement('div');
                tagElement.className = 'seup-tag-selected';
                tagElement.innerHTML = `
                    <i class="fas fa-tag" style="color: var(--seup-${tag.color}, #3b82f6);"></i>
                    ${tag.name}
                    <button class="seup-tag-remove-btn" onclick="window.seupTagsModal.removeTag(${tag.id})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                displayArea.appendChild(tagElement);
            }
        });
    }

    updateFormInputs() {
        // Remove existing hidden inputs
        document.querySelectorAll('input[name="tags[]"]').forEach(input => {
            input.remove();
        });
        
        // Add new hidden inputs for selected tags
        const form = document.querySelector('form');
        if (form) {
            this.selectedTags.forEach(tagId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tags[]';
                input.value = tagId;
                form.appendChild(input);
            });
        }
    }

    removeTag(tagId) {
        this.selectedTags.delete(tagId);
        this.updateSelectedTagsDisplay();
        this.updateFormInputs();
        this.updateSelectedCount();
        
        // Update modal if open
        const tagElement = document.querySelector(`[data-tag-id="${tagId}"]`);
        if (tagElement) {
            tagElement.classList.remove('selected');
        }
    }
}

// Initialize tags modal when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.seupTagsModal = new SEUPTagsModal();
});

// Export for global use
window.SEUPTagsModal = SEUPTagsModal;