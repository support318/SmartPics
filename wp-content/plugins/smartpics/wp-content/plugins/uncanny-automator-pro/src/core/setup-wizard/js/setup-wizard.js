// Get reference to the activate license button, form, and feedback element.
const activateLicenseBtn = document.getElementById('license_key_btn');
const formElement = document.getElementById('license_key_form');
const feedbackElement = document.getElementById('feedback');

// Function to clear feedback message and error class.
const clearFeedback = () => {
    feedbackElement.innerHTML = '';
    feedbackElement.classList.add('hidden');
    feedbackElement.classList.remove('has-error', 'success');
    feedbackElement.setAttribute('type', '');
}

// Function to toggle preloader state.
const togglePreloader = (isLoading) => {
    activateLicenseBtn.classList.toggle('loading', isLoading);
    isLoading ? activateLicenseBtn.setAttribute('loading', '') : activateLicenseBtn.removeAttribute('loading');
    isLoading ? activateLicenseBtn.setAttribute('disabled', 'true') : activateLicenseBtn.removeAttribute('disabled');
}

// Function to display message with optional error styling.
const displayMessage = (message, isError) => {
    feedbackElement.innerHTML = message;
    feedbackElement.classList.toggle('hidden', !message);
    feedbackElement.classList.toggle('has-error', isError);
    feedbackElement.classList.toggle('success', !isError);
    feedbackElement.setAttribute('type', isError ? 'error' : 'success');
}

// Event listener for form submission.
formElement.addEventListener('submit', async (e) => {
    e.preventDefault(); // Prevent default form submission.
    await handleSubmit();
});

// Event listener for button click.
activateLicenseBtn.addEventListener('click', async () => {
    await handleSubmit();
});

// Function to handle form submission and button click.
const handleSubmit = async () => {
    clearFeedback(); // Clear any previous feedback.
    togglePreloader(true); // Show preloader.

    try {
        await sendRequest(); // Send request asynchronously.
    } catch (error) {
        displayMessage(error.message || 'An unexpected error occurred.', true); // Display error message.
    }

    togglePreloader(false); // Hide preloader after request completes.
}

// Asynchronous function to send request.
const sendRequest = async () => {
    const licenseKey = document.getElementById('license_key').value; // Get license key from input field.
    const data = { nonce: automatorSetupWizard.nonce, license: licenseKey }; // Prepare data for request.

    try {
        const response = await fetch(automatorSetupWizard.endpoint, { // Send POST request.
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (!response.ok) { // If response is not OK, throw error.
            const errorData = await response.json();
            throw new Error(errorData?.message || 'Network response was not ok');
        }

        // Display success message and reload page after 3 seconds.
        displayMessage(automatorSetupWizard.messageSuccess, false);
        setTimeout(() => { location.reload(); }, 3000);
    } catch (error) {
        throw error; // Throw error if request fails.
    }
}
