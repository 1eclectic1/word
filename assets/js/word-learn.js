/**
 * word-learn.js - Client-side interaction logic
 */

document.addEventListener('DOMContentLoaded', () => {
	// If the server marked the puzzle solved, lock the UI
	if (document.body.dataset.solved === '1') {
		// nothing more to do – grid.php already made rows non-editable
	}
	const form = document.getElementById('wordle-form');
	if (!form) return; // Help mode has no form

	// Always force the current guess into the hidden field before any submit
	form.addEventListener('submit', (e) => {
		const word = syncCurrentGuess();
		if (word.length !== 5) {
			e.preventDefault();
			alert('Please enter a full 5-letter word.');
			return;
		}
		if (document.body.dataset.solved === '1') {
			e.preventDefault();
			return;
		}
	});
	const rows = document.querySelectorAll('.word-row');
	const activeRowIndex = Array.from(rows).findIndex(row => {
		return row.querySelector('.letter-box:not([readonly])') !== null;
	});

	const currentMode = new URLSearchParams(window.location.search).get('mode') || 'learn';

	if (activeRowIndex === -1 || activeRowIndex >= 6) return;

	const inputs = rows[activeRowIndex].querySelectorAll('.letter-box');

	// Focus first empty box on load (only for first row)
	if (inputs.length > 0 && activeRowIndex === 0) {
		inputs[0].focus();
	}

	// ---------- Physical keyboard ----------
	inputs.forEach((input, index) => {
		input.addEventListener('input', (e) => {
			if (e.target.value.length >= 1) {
				e.target.value = e.target.value.charAt(0).toUpperCase();
				if (index < inputs.length - 1) {
					inputs[index + 1].focus();
				}
			}
			syncLearnHiddenField();
		});

		input.addEventListener('keydown', (e) => {
			if (e.key === 'Backspace') {
				if (e.target.value.length === 0 && index > 0) {
					inputs[index - 1].focus();
					inputs[index - 1].value = '';
					e.preventDefault();
				}
			}

			if (e.key === 'Enter') {
				e.preventDefault();
				submitIfComplete();
			}

			syncLearnHiddenField();
		});
	});

	// ---------- Virtual keyboard ----------
	document.querySelectorAll('.key').forEach(keyButton => {
		keyButton.addEventListener('click', (e) => {
			e.preventDefault();
			const action = keyButton.getAttribute('data-key');

			if (action === 'delete') {
				for (let i = inputs.length - 1; i >= 0; i--) {
					if (inputs[i].value !== '') {
						inputs[i].value = '';
						inputs[i].focus();
						break;
					}
				}
				syncLearnHiddenField();
				return;
			}

			if (action === 'enter') {
				submitIfComplete();
				return;
			}

			// Letter key
			const letter = action.toUpperCase();
			for (let i = 0; i < inputs.length; i++) {
				if (inputs[i].value === '') {
					inputs[i].value = letter;
					if (i < inputs.length - 1) {
						inputs[i + 1].focus();
					}
					break;
				}
			}
			syncLearnHiddenField();
		});
	});

	// ---------- Solver mode: cycle colors ----------
	document.body.addEventListener('click', (e) => {
		if (e.target.matches('.letter-box[data-clickable="true"]')) {
			const box = e.target;
			const r = box.getAttribute('data-row');
			const c = box.getAttribute('data-col');
			const hidden = document.getElementById(`color-${r}-${c}`);

			const cycle = ['gray', 'green', 'yellow'];
			let currentIdx = cycle.indexOf(hidden.value);
			let nextIdx = (currentIdx + 1) % cycle.length;
			let nextState = cycle[nextIdx];

			box.classList.remove('state-gray', 'state-yellow', 'state-green');
			box.classList.add(`state-${nextState}`);
			hidden.value = nextState;
		}
	});
	// ... (All your existing physical keyboard, virtual keyboard, and solver logic remains unchanged up here) ...


	function syncCurrentGuess() {
		// Always rebuild from the currently editable letter boxes
		const activeRow = document.querySelector('.word-row .letter-box:not([readonly])')?.closest('.word-row');
		if (!activeRow) return '';

		let word = '';
		activeRow.querySelectorAll('.letter-box').forEach(box => {
			word += (box.value || '').trim().toUpperCase();
		});
		word = word.toLowerCase();

		const hidden = document.getElementById('current-guess');
		if (hidden) hidden.value = word;
		return word;
	}

	function submitIfComplete() {
		// Re-sync right before we decide
		const word = syncCurrentGuess();

		if (word.length !== 5) {
			alert('Please enter a full 5-letter word.');
			return;
		}

		// Prevent double-submit or submit after solved
		if (document.body.dataset.solved === '1') {
			return;
		}

		document.activeElement?.blur();

		// Force the hidden field one last time
		const currentGuessInput = document.getElementById('current-guess');
		if (currentGuessInput) {
			currentGuessInput.value = word;
		}

		// Telemetry (only relevant for learn mode, but harmless)
		const secretElement = document.getElementById('secret-word-display');
		const secretWord = secretElement ? secretElement.textContent.trim().toLowerCase() : '';
		const turnsCount = (document.querySelectorAll('.word-row .letter-box[readonly]').length / 5) + 1;

		if (secretWord && secretWord.length === 5) {
			if (word === secretWord) {
				injectTelemetryFields(secretWord, 'win', turnsCount);
			} else if (turnsCount === 6) {
				injectTelemetryFields(secretWord, 'loss', 0);
			}
		}

		form.submit();
	}

	/**
	 * Creates hidden form inputs on the fly to transmit stats data payload with the post request
	 */
	function injectTelemetryFields(secret, outcome, turns) {
		const inputConfigs = [
			{ name: 'record_game_telemetry', value: '1' },
			{ name: 'secret_word', value: secret },
			{ name: 'outcome', value: outcome },
			{ name: 'turns_taken', value: turns.toString() }
		];

		inputConfigs.forEach(cfg => {
			const hiddenField = document.createElement('input');
			hiddenField.type = 'hidden';
			hiddenField.name = cfg.name;
			hiddenField.value = cfg.value;
			form.appendChild(hiddenField);
		});
	}

	// ---------- Suggestions Visibility Toggle ----------
	const toggleBtn = document.getElementById('toggle-suggestions-btn');
	const predictionsContent = document.getElementById('predictions-content');

	if (toggleBtn && predictionsContent) {
		// Read user preference from local storage
		const isHidden = localStorage.getItem('hideSuggestions') === 'true';

		// Apply state immediately on page load/reload
		if (isHidden) {
			predictionsContent.style.display = 'none';
			toggleBtn.textContent = 'Show Suggestions';
			toggleBtn.classList.add('btn-disabled');
		}

		// Click handler to toggle layout visibility 
		toggleBtn.addEventListener('click', () => {
			const currentlyHidden = predictionsContent.style.display === 'none';

			if (currentlyHidden) {
				predictionsContent.style.display = 'block';
				toggleBtn.textContent = 'Hide Suggestions';
				toggleBtn.classList.remove('btn-disabled');
				localStorage.setItem('hideSuggestions', 'false');
			} else {
				predictionsContent.style.display = 'none';
				toggleBtn.textContent = 'Show Suggestions';
				toggleBtn.classList.add('btn-disabled');
				localStorage.setItem('hideSuggestions', 'true');
			}
		});
	}

	// Auto-scroll to suggestions on later rows (Updated to check visibility state)
	const panel = document.getElementById('predictions');
	if (panel && activeRowIndex > 0 && localStorage.getItem('hideSuggestions') !== 'true') {
		// BUG FIX: Wrapping in a 100ms timeout prevents the browser focus sequence
		// from fighting the smooth scroll handler when row typing begins.
		setTimeout(() => {
			panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}, 100);
	}
}); // <--- THIS CLOSES 'DOMContentLoaded' VERY IMPORTANT

// Global reset helper (called from button) - SITS OUTSIDE DOMCONTENTLOADED
function triggerReset() {
	const actionInput = document.getElementById('form-action');
	if (actionInput) {
		actionInput.value = 'clear';
	}
	const form = document.getElementById('wordle-form');
	if (form) form.submit();
}

