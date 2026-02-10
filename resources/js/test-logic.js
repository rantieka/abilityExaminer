/**
 * TestLogic Class
 * Handles countdown timer and auto-saving of answers to localStorage.
 */
export class TestLogic {
    constructor(options) {
        this.appId = options.appId;
        this.part = options.part; // 'part1' or 'part2'
        this.remainingSeconds = parseInt(options.remainingSeconds, 10);
        if (isNaN(this.remainingSeconds)) this.remainingSeconds = 0;

        console.log("TestLogic Init:", options, "Parsed Remaining:", this.remainingSeconds);

        this.timerElement = document.getElementById(options.timerId);
        this.form = document.getElementById(options.formId || 'testForm');
        this.storageKey = `test_answers_${this.appId}_${this.part}`;

        this.initTimer();
        this.initAutoSave();
    }

    initTimer() {
        if (!this.timerElement) return;

        let timeLeft = this.remainingSeconds;

        const updateDisplay = () => {
            if (timeLeft < 0) timeLeft = 0;

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            this.timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Visual warning when low
            if (timeLeft < 300) { // < 5 mins
                this.timerElement.classList.add('text-danger', 'fw-bold');
                this.timerElement.classList.remove('text-dark');
            }
        };

        updateDisplay(); // Initial call

        this.timerInterval = setInterval(() => {
            if (timeLeft > 0) {
                timeLeft--;
                updateDisplay();
            } else {
                clearInterval(this.timerInterval);
                alert("Waktu Habis! Jawaban Anda akan dikirim otomatis.");
                this.form.submit();
            }
        }, 1000);
    }

    initAutoSave() {
        if (!this.form) return;

        // Restore answers from LocalStorage
        this.loadAnswers();

        // Listen for changes
        this.form.addEventListener('change', (e) => {
            const input = e.target;
            if (input.name && input.name.startsWith('answers')) {
                this.saveAnswer(input);
            }
        });

        // Also listen for text input (textarea)
        this.form.addEventListener('input', (e) => {
            const input = e.target;
            if (input.tagName === 'TEXTAREA' || input.type === 'text') {
                this.saveAnswer(input);
            }
        });
    }

    saveAnswer(input) {
        const stored = JSON.parse(localStorage.getItem(this.storageKey) || '{}');

        if (input.type === 'radio' || input.type === 'checkbox') {
            if (input.checked) {
                stored[input.name] = input.value;
            }
        } else {
            stored[input.name] = input.value;
        }

        localStorage.setItem(this.storageKey, JSON.stringify(stored));

        // Optional: clear on submit? Handled by submit handler if needed.
    }

    loadAnswers() {
        const stored = JSON.parse(localStorage.getItem(this.storageKey) || '{}');

        Object.keys(stored).forEach(name => {
            const value = stored[name];
            const input = this.form.querySelector(`[name="${name}"]`);

            // Handle Radio Buttons (Single)
            const radios = this.form.querySelectorAll(`[name="${name}"]`);
            if (radios.length > 0 && radios[0].type === 'radio') {
                radios.forEach(radio => {
                    if (radio.value === value) {
                        radio.checked = true;
                    }
                });
                return;
            }

            // Handle Text Inputs / Textareas
            if (input) {
                input.value = value;
            }
        });
    }

    clearStorage() {
        localStorage.removeItem(this.storageKey);
    }
}

// Helper to auto-init if window global is set
window.TestLogic = TestLogic;

// Auto-init based on data attributes
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('test-container');
    if (container) {
        new TestLogic({
            appId: container.dataset.appId,
            part: container.dataset.part,
            remainingSeconds: container.dataset.remaining,
            timerId: 'time',
            formId: 'testForm'
        });
    }
});
