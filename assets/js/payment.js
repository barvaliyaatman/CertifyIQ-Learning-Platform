document.addEventListener('DOMContentLoaded', function() {
    // Card number formatting
    const cardInput = document.querySelector('input[name="card_number"]');
    cardInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 16) value = value.slice(0, 16);
        let formattedValue = value.replace(/(\d{4})/g, '$1 ').trim();
        e.target.value = formattedValue;
    });

    // Expiry date formatting
    const expiryInput = document.querySelector('input[name="expiry"]');
    expiryInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        if (value.length > 2) {
            value = value.slice(0, 2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });

    // CVV formatting
    const cvvInput = document.querySelector('input[name="cvv"]');
    cvvInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 3) value = value.slice(0, 3);
        e.target.value = value;
    });

    // Form validation
    const paymentForm = document.querySelector('.payment-form');
    paymentForm.addEventListener('submit', function(e) {
        const cardNumber = cardInput.value.replace(/\s/g, '');
        const expiry = expiryInput.value;
        const cvv = cvvInput.value;
        
        // Validate card number format
        if (!/^[0-9]{16}$/.test(cardNumber)) {
            e.preventDefault();
            alert('Please enter a valid 16-digit card number.');
            return;
        }
        
        // Validate expiry date format
        if (!/^(0[1-9]|1[0-2])\/[0-9]{2}$/.test(expiry)) {
            e.preventDefault();
            alert('Please enter a valid expiry date in MM/YY format.');
            return;
        }
        
        // Validate CVV format
        if (!/^[0-9]{3,4}$/.test(cvv)) {
            e.preventDefault();
            alert('Please enter a valid CVV (3 or 4 digits).');
            return;
        }
    });
});