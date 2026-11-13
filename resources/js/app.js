import './qr-scan.js';
import './location-picker.js';

document.addEventListener('invalid', (event) => {
	const field = event.target;

	if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
		return;
	}

	const fieldContainer = field.closest('.form-field') ?? field.parentElement;

	if (!fieldContainer) {
		return;
	}

	let feedback = fieldContainer.querySelector('[data-client-validation-error]');

	if (!feedback) {
		feedback = document.createElement('p');
		feedback.className = 'field-error';
		feedback.dataset.clientValidationError = '';
		feedback.setAttribute('role', 'alert');

		const controlGroup = field.parentElement;
		const insertionPoint = controlGroup !== fieldContainer && controlGroup?.parentElement === fieldContainer
			? controlGroup
			: field;

		insertionPoint.insertAdjacentElement('afterend', feedback);
	}

	feedback.textContent = field.validationMessage;
	field.setAttribute('aria-invalid', 'true');
}, true);

function clearClientValidationError(event) {
	const field = event.target;

	if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)
		|| !field.validity.valid) {
		return;
	}

	const fieldContainer = field.closest('.form-field') ?? field.parentElement;
	const feedback = fieldContainer?.querySelector('[data-client-validation-error]');
	feedback?.remove();

	if (!fieldContainer?.querySelector('.field-error')) {
		field.removeAttribute('aria-invalid');
	}
}

document.addEventListener('input', clearClientValidationError);
document.addEventListener('change', clearClientValidationError);

document.addEventListener('submit', (event) => {
	const message = event.target instanceof HTMLFormElement ? event.target.dataset.confirm : null;

	if (message && !window.confirm(message)) {
		event.preventDefault();
	}
});
