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