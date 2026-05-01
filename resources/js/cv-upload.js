/**
 * Handle CV File Upload Validation
 */
document.addEventListener('DOMContentLoaded', function () {
  const cvInput = document.getElementById('cv_input');
  const alertBox = document.getElementById('file-error-alert');

  if (cvInput) {
    cvInput.onchange = function () {
      if (this.files && this.files[0]) {
        if (this.files[0].size > 2097152) { // 2MB in bytes
          if (alertBox) alertBox.classList.remove('d-none');
          this.value = ""; // Clear the input
        } else {
          if (alertBox) alertBox.classList.add('d-none');
        }
      }
    };
  }
});
