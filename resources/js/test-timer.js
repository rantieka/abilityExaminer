document.addEventListener('DOMContentLoaded', function () {
  // Timer & Auto-Save Logic Shared for Part 1 & Part 2

  const timerElement = document.getElementById('time');
  const countdownContainer = document.getElementById('test-container');
  const form = document.getElementById('testForm');

  if (timerElement && countdownContainer) {
    // Get remaining time from server (passed via data attribute)
    // If undefined or invalid, fallback to 30 mins (1800s)
    let remaining = parseInt(countdownContainer.dataset.remaining);
    if (isNaN(remaining)) remaining = 30 * 60;

    function updateDisplay(timeLeft) {
      // Prevent negative time display
      if (timeLeft < 0) timeLeft = 0;

      const minutes = Math.floor(timeLeft / 60);
      const seconds = timeLeft % 60;
      timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

      // Visual warning when time is low (< 5 mins)
      if (timeLeft < 300) {
        timerElement.classList.add('text-danger');
        timerElement.classList.remove('text-dark');
      } else {
        timerElement.classList.add('text-dark');
        timerElement.classList.remove('text-danger');
      }
    }

    // Initial render
    updateDisplay(remaining);

    // Start countdown
    const timerInterval = setInterval(() => {
      if (remaining > 0) {
        remaining--;
        updateDisplay(remaining);
      } else {
        clearInterval(timerInterval);
        alert("Waktu Habis! Jawaban Anda akan dikirim otomatis.");
        if (form) form.submit();
      }
    }, 1000);
  }

  // Auto-Save to LocalStorage
  if (form && countdownContainer) {
    // Unique key per Applicant + Part (e.g., answers_app_60_part1)
    const appId = countdownContainer.dataset.appId;
    const part = countdownContainer.dataset.part;
    const storageKey = `answers_app_${appId}_${part}`;

    // Load saved answers
    try {
      const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');

      // Populate inputs
      Object.keys(saved).forEach(key => {
        const inputs = form.querySelectorAll(`[name="${key}"]`);
        if (inputs.length > 0) {
          inputs.forEach(input => {
            // Radio buttons
            if (input.type === 'radio' && input.value === saved[key]) {
              input.checked = true;
            }
            // Other inputs (if any)
            else if (input.type !== 'radio' && input.type !== 'checkbox') {
              input.value = saved[key];
            }
          });
        }
      });

      // Save on change
      form.addEventListener('change', (e) => {
        const input = e.target;
        if (input.name) {
          saved[input.name] = input.value;
          localStorage.setItem(storageKey, JSON.stringify(saved));
        }
      });

      // Clear storage on submit
      form.addEventListener('submit', () => {
        localStorage.removeItem(storageKey);
      });

    } catch (e) {
      console.error("Local storage error:", e);
    }
  }
});
