/**
 * SEUP Modern JavaScript Library
 * Enhanced interactions and animations
 */

class SEUPModern {
    constructor() {
        this.init();
    }

    init() {
        this.setupAnimations();
        this.setupInteractions();
        this.setupFormEnhancements();
        this.setupTableEnhancements();
        this.setupNotifications();
    }

    // Animation system
    setupAnimations() {
        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('seup-fade-in');
                }
            });
        }, observerOptions);

        // Observe all cards and major elements
        document.querySelectorAll('.seup-card, .seup-table, .seup-hero').forEach(el => {
            observer.observe(el);
        });
    }

    // Enhanced interactions
    setupInteractions() {
        // Button ripple effect
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('seup-btn')) {
                this.createRipple(e);
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced hover effects for interactive elements
        document.querySelectorAll('.seup-interactive').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'translateY(-2px)';
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'translateY(0)';
            });
        });
    }

    // Form enhancements
    setupFormEnhancements() {
        // Auto-resize textareas
        document.querySelectorAll('.seup-textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            });
        });

        // Enhanced form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });

        // Real-time validation feedback
        document.querySelectorAll('.seup-input, .seup-select, .seup-textarea').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    // Table enhancements
    setupTableEnhancements() {
        // Sortable headers
        document.querySelectorAll('.seup-sortable-header').forEach(header => {
            header.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSort(header);
            });
        });

        // Row selection
        document.querySelectorAll('.seup-table tbody tr').forEach(row => {
            row.addEventListener('click', (e) => {
                if (!e.target.closest('button, a')) {
                    row.classList.toggle('selected');
                }
            });
        });
    }

    // Notification system
    setupNotifications() {
        // Auto-hide notifications after 5 seconds
        document.querySelectorAll('.seup-alert').forEach(alert => {
            setTimeout(() => {
                this.fadeOut(alert);
            }, 5000);
        });
    }

    // Utility methods
    createRipple(event) {
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: seup-ripple 0.6s ease-out;
            pointer-events: none;
        `;

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes seup-ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Ovo polje je obavezno');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    }

    validateField(field) {
        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showFieldError(field, 'Ovo polje je obavezno');
            return false;
        }

        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                this.showFieldError(field, 'Unesite valjanu email adresu');
                return false;
            }
        }

        this.clearFieldError(field);
        return true;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        field.style.borderColor = 'var(--seup-error)';
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.seup-field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'seup-field-error';
        errorDiv.style.cssText = `
            color: var(--seup-error);
            font-size: 0.75rem;
            margin-top: var(--seup-space-1);
        `;
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        field.style.borderColor = '';
        
        const errorDiv = field.parentNode.querySelector('.seup-field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    handleSort(header) {
        // Add loading state
        header.classList.add('seup-loading');
        
        // Simulate sorting (actual implementation would depend on backend)
        setTimeout(() => {
            header.classList.remove('seup-loading');
        }, 500);
    }

    fadeOut(element) {
        element.style.transition = 'opacity 0.3s ease';
        element.style.opacity = '0';
        
        setTimeout(() => {
            element.remove();
        }, 300);
    }

    // Public API methods
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `seup-alert seup-alert-${type} seup-fade-in`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
            max-width: 500px;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            this.fadeOut(notification);
        }, duration);
    }

    showLoading(element) {
        const spinner = document.createElement('div');
        spinner.className = 'seup-spinner';
        spinner.style.marginRight = 'var(--seup-space-2)';
        
        element.prepend(spinner);
        element.disabled = true;
    }

    hideLoading(element) {
        const spinner = element.querySelector('.seup-spinner');
        if (spinner) {
            spinner.remove();
        }
        element.disabled = false;
    }

    // Enhanced dropdown functionality
    createDropdown(trigger, items) {
        const dropdown = document.createElement('div');
        dropdown.className = 'seup-dropdown-menu seup-fade-in';
        
        items.forEach(item => {
            const dropdownItem = document.createElement('div');
            dropdownItem.className = 'seup-dropdown-item';
            dropdownItem.textContent = item.label;
            dropdownItem.addEventListener('click', () => {
                item.action();
                dropdown.remove();
            });
            dropdown.appendChild(dropdownItem);
        });

        trigger.parentNode.appendChild(dropdown);

        // Close on outside click
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && e.target !== trigger) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 0);
    }

    // File upload with progress
    uploadFile(file, url, onProgress, onComplete) {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                onProgress(percentComplete);
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                onComplete(JSON.parse(xhr.responseText));
            }
        });

        xhr.open('POST', url);
        xhr.send(formData);
    }

    // Debounce utility
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Theme switcher
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('seup-theme', newTheme);
    }

    // Initialize theme from localStorage
    initTheme() {
        const savedTheme = localStorage.getItem('seup-theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.seupModern = new SEUPModern();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SEUPModern;
}