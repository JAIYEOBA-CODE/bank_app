// FedBank Digital Wallet - Main JavaScript

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const mobileMenuBtn = document.querySelector('.btn.rounded-circle');
    
    if (window.innerWidth <= 768 && sidebar) {
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnToggle = (toggleBtn && toggleBtn.contains(e.target)) || 
                                (mobileMenuBtn && mobileMenuBtn.contains(e.target));
        
        if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});

// Close sidebar when clicking on a sidebar link on mobile
document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                setTimeout(() => sidebar.classList.remove('show'), 300);
            }
        }
    });
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Password confirmation match
const confirmPasswordInput = document.getElementById('confirm_password');
if (confirmPasswordInput) {
    const passwordInput = document.getElementById('password');
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-permanent')) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }
    });
}, 5000);

// Format currency input
function formatCurrencyInput(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d.]/g, '');
        e.target.value = value;
    });
}

// Initialize currency inputs
document.querySelectorAll('input[type="number"][data-currency]').forEach(input => {
    formatCurrencyInput(input);
});

// Animate on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements with animate class
document.querySelectorAll('.animate-slide-up, .animate-fade-in').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// Dashboard specific functions
if (document.querySelector('.dashboard')) {
    // Update balance animation
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = start + (end - start) * progress;
            element.textContent = '₦' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
    
    // Animate balance on load
    const balanceElement = document.querySelector('.balance-amount');
    if (balanceElement) {
        const balance = parseFloat(balanceElement.dataset.balance || 0);
        animateValue(balanceElement, 0, balance, 1000);
    }
}

// Prevent form resubmission on refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

