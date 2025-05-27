document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.querySelector('input[type="password"]');
    const togglePasswordBtn = document.querySelector('.toggle-password');
   
    togglePasswordBtn.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        
        togglePasswordBtn.querySelector('i').classList.toggle('fa-eye');
        togglePasswordBtn.querySelector('i').classList.toggle('fa-eye-slash');
    });
   
    document.querySelector('.login-form').addEventListener('submit', (e) => {
        const email = document.querySelector('input[type="email"]').value;
        const password = passwordInput.value;

        if (!email || !password) {
            e.preventDefault();
            alert('Please fill in all fields');
        }
    });
});
