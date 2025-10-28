// Custom JavaScript for 24Earn

// Disable right-click context menu to prevent viewing source
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});

// Disable F12, Ctrl+Shift+I, Ctrl+U, etc. to prevent developer tools
document.addEventListener('keydown', function(e) {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I') || (e.ctrlKey && e.key === 'U')) {
        e.preventDefault();
    }
});

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Form validation for registration
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('#register form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }

    // Deposit form validation
    const depositForm = document.querySelector('#depositModal form');
    if (depositForm) {
        depositForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('deposit_amount').value);
            if (amount < 10) {
                e.preventDefault();
                alert('Minimum deposit is $10!');
            }
        });
    }

    // Withdraw form validation
    const withdrawForm = document.querySelector('#withdrawModal form');
    if (withdrawForm) {
        withdrawForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('withdraw_amount').value);
            const maxAmount = parseFloat(document.getElementById('withdraw_amount').getAttribute('max'));
            const addressType = document.querySelector('input[name="address_type"]:checked').value;
            let address = '';
            if (addressType === 'saved') {
                address = document.getElementById('saved_address_select').value;
            } else {
                address = document.getElementById('withdraw_wallet').value;
            }
            if (amount > maxAmount) {
                e.preventDefault();
                alert('Insufficient balance!');
            } else if (!address.trim()) {
                e.preventDefault();
                alert('Please select or enter a withdraw address!');
            }
        });
    }

    // Toggle address input based on radio button selection
    const savedAddressRadio = document.getElementById('saved_address_radio');
    const newAddressRadio = document.getElementById('new_address_radio');
    const savedAddressSelect = document.getElementById('saved_address_select');
    const newAddressInput = document.getElementById('withdraw_wallet');

    if (savedAddressRadio && newAddressRadio && savedAddressSelect && newAddressInput) {
        savedAddressRadio.addEventListener('change', function() {
            if (this.checked) {
                savedAddressSelect.style.display = 'block';
                newAddressInput.style.display = 'none';
                newAddressInput.required = false;
                savedAddressSelect.required = true;
            }
        });

        newAddressRadio.addEventListener('change', function() {
            if (this.checked) {
                savedAddressSelect.style.display = 'none';
                newAddressInput.style.display = 'block';
                savedAddressSelect.required = false;
                newAddressInput.required = true;
            }
        });
    }

    // Chat message limit check
    const messageInput = document.getElementById('message');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            if (this.value.length > 500) {
                this.value = this.value.substring(0, 500);
            }
        });
    }
});

// Auto-scroll chat to bottom
function scrollToBottom() {
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// Call scrollToBottom on page load
document.addEventListener('DOMContentLoaded', scrollToBottom);
