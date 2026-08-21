/**
 * word-learn.js - Client-side interaction logic
 * (Telemetry completely removed – now handled server-side)
 */

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.dataset.solved === '1') {
        // board already locked by PHP
    }

    const form = document.getElementById('wordle-form');
    if (!form) return;

    // Guard any native submit
    form.addEventListener('submit', (e) => {
        const word = syncCurrentGuess();
        if (word.length !== 5) {
            e.preventDefault();
            alert('Please enter a full 5-letter word.');
            return;
        }
        if (document.body.dataset.solved === '1') {
            e.preventDefault();
        }
    });

    const rows = document.querySelectorAll('.word-row');
    const activeRowIndex = Array.from(rows).findIndex(row =>
        row.querySelector('.letter-box:not([readonly])') !== null
    );

    if (activeRowIndex === -1 || activeRowIndex >= 6) return;

    const inputs = rows[activeRowIndex].querySelectorAll('.letter-box');

    // Focus first empty box
    if (inputs.length > 0 && activeRowIndex === 0) {
        inputs[0].focus();
    }

    // Physical keyboard
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length >= 1) {
                e.target.value = e.target.value.charAt(0).toUpperCase();
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
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
        });
    });

    // Virtual keyboard
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
        });
    });

    // Solver mode: cycle colors
    document.body.addEventListener('click', (e) => {
        if (e.target.matches('.letter-box[data-clickable="true"]')) {
            const box = e.target;
            const r = box.getAttribute('data-row');
            const c = box.getAttribute('data-col');
            const hidden = document.getElementById(`color-${r}-${c}`);
            if (!hidden) return;

            const cycle = ['gray', 'green', 'yellow'];
            let currentIdx = cycle.indexOf(hidden.value);
            let nextIdx = (currentIdx + 1) % cycle.length;
            let nextState = cycle[nextIdx];

            box.classList.remove('state-gray', 'state-yellow', 'state-green');
            box.classList.add(`state-${nextState}`);
            hidden.value = nextState;
        }
    });

    // Suggestions toggle
    const toggleBtn = document.getElementById('toggle-suggestions-btn');
    const predictionsContent = document.getElementById('predictions-content');

    if (toggleBtn && predictionsContent) {
        const isHidden = localStorage.getItem('hideSuggestions') === 'true';
        if (isHidden) {
            predictionsContent.style.display = 'none';
            toggleBtn.textContent = 'Show Suggestions';
            toggleBtn.classList.add('btn-disabled');
        }

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

    // Auto-scroll to suggestions
    const panel = document.getElementById('predictions');
    if (panel && activeRowIndex > 0 && localStorage.getItem('hideSuggestions') !== 'true') {
        setTimeout(() => {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
});

// ===== GLOBAL HELPERS =====

function syncCurrentGuess() {
    const activeRow = document.querySelector('.word-row .letter-box:not([readonly])')?.closest('.word-row');
    if (!activeRow) return '';

    let word = '';
    activeRow.querySelectorAll('.letter-box').forEach(box => {
        word += (box.value || '').trim().toUpperCase();
    });
    return word.toLowerCase();
}

function submitIfComplete() {
    const form = document.getElementById('wordle-form');
    if (!form) return;

    const word = syncCurrentGuess();

    if (word.length !== 5) {
        alert('Please enter a full 5-letter word.');
        return;
    }

    if (document.body.dataset.solved === '1') {
        return;
    }

    document.activeElement?.blur();

    const currentGuessInput = document.getElementById('current-guess');
    if (currentGuessInput) {
        currentGuessInput.value = word;
    }

    form.submit();
}

function triggerReset() {
    const actionInput = document.getElementById('form-action');
    if (actionInput) {
        actionInput.value = 'clear';
    }
    const form = document.getElementById('wordle-form');
    if (form) form.submit();
}

