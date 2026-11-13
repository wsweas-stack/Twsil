const container = document.getElementById('location-picker-map');
const dialog = document.getElementById('location-picker-dialog');

if (container instanceof HTMLElement && dialog instanceof HTMLDialogElement) {
	const apiKey = container.dataset.mapsKey;
	const initialLat = Number.parseFloat(container.dataset.initialLat);
	const initialLng = Number.parseFloat(container.dataset.initialLng);
	const hasSavedLocation = Number.isFinite(initialLat) && Number.isFinite(initialLng);
	const openButton = document.querySelector('[data-location-picker-open]');
	const closeButton = document.querySelector('[data-location-picker-close]');
	const errorMessage = document.getElementById('location-picker-error');
	const latitudeInput = document.getElementById('location-latitude');
	const longitudeInput = document.getElementById('location-longitude');
	const coordinatesLabel = document.getElementById('location-picker-coordinates');
	const unsavedHint = document.getElementById('location-picker-unsaved');
	const confirmButton = document.getElementById('location-picker-confirm');
	let map = null;
	let marker = null;

	function trimCoordinate(value) {
		return Number.parseFloat(value.toFixed(7));
	}

	function updateSelection(latitude, longitude) {
		latitudeInput.value = trimCoordinate(latitude);
		longitudeInput.value = trimCoordinate(longitude);
		coordinatesLabel.textContent = `${latitudeInput.value}, ${longitudeInput.value}`;
		unsavedHint.hidden = false;
		confirmButton.disabled = false;

		const position = new google.maps.LatLng({ lat: latitude, lng: longitude });

		if (marker) {
			marker.setPosition(position);

			return;
		}

		marker = new google.maps.Marker({
			position,
			map,
			draggable: true,
			title: coordinatesLabel.textContent,
		});
		marker.addListener('dragend', () => {
			const here = marker.getPosition();

			if (here) {
				updateSelection(here.lat(), here.lng());
			}
		});
	}

	function resetToSavedLocation() {
		if (hasSavedLocation) {
			updateSelection(initialLat, initialLng);
		}

		unsavedHint.hidden = true;
		confirmButton.disabled = !hasSavedLocation;
		coordinatesLabel.textContent = hasSavedLocation
			? `${trimCoordinate(initialLat)}, ${trimCoordinate(initialLng)}`
			: '';
	}

	function drawMap() {
		const center = hasSavedLocation ? { lat: initialLat, lng: initialLng } : { lat: 24.7136, lng: 46.6753 };

		map = new google.maps.Map(container, {
			center,
			zoom: hasSavedLocation ? 16 : 11,
			clickableIcons: false,
		});

		map.addListener('click', (event) => {
			if (event.latLng) {
				updateSelection(event.latLng.lat(), event.latLng.lng());
			}
		});

		if (hasSavedLocation) {
			updateSelection(initialLat, initialLng);
		} else {
			resetToSavedLocation();
		}
	}

	function loadGoogleMapsSdk() {
		if (window.google?.maps) {
			return Promise.resolve();
		}

		const existing = document.getElementById('google-maps-sdk');

		if (existing instanceof HTMLScriptElement) {
			return new Promise((resolve, reject) => {
				existing.addEventListener('load', () => resolve(), { once: true });
				existing.addEventListener('error', () => reject(new Error('maps-load-failed')), { once: true });
			});
		}

		return new Promise((resolve, reject) => {
			const script = document.createElement('script');
			script.id = 'google-maps-sdk';
			script.async = true;
			script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly&language=${container.dataset.locale}`;
			script.addEventListener('load', () => resolve(), { once: true });
			script.addEventListener('error', () => {
				script.remove();
				document.getElementById('google-maps-sdk')?.remove();
				reject(new Error('maps-load-failed'));
			}, { once: true });
			document.head.append(script);
		});
	}

	function openPicker() {
		if (dialog.open) {
			dialog.removeAttribute('open');
		}

		dialog.showModal();

		if (map) {
			return;
		}

		loadGoogleMapsSdk()
			.then(() => {
				container.classList.remove('is-loading');
				container.classList.remove('is-error');
				errorMessage.hidden = true;
				drawMap();
			})
			.catch(() => {
				container.classList.remove('is-loading');
				container.classList.add('is-error');
				errorMessage.hidden = false;
			});

		container.classList.add('is-loading');
	}

	openButton?.addEventListener('click', openPicker);

	if (dialog.open) {
		openPicker();
	}

	closeButton?.addEventListener('click', () => dialog.close());

	dialog.addEventListener('close', () => {
		const latitude = Number.parseFloat(latitudeInput.value);
		const longitude = Number.parseFloat(longitudeInput.value);
		const selectedOnMap = map
			&& Number.isFinite(latitude)
			&& Number.isFinite(longitude)
			&& !(latitude === initialLat && longitude === initialLng);

		if (!selectedOnMap) {
			resetToSavedLocation();
			return;
		}

		unsavedHint.hidden = false;
	});
}
