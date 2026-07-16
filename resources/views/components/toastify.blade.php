<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
<style>
  .toast-custom .toast-close {
    color: #9ca3af !important; /* Tailwind gray-400 */
    opacity: 1 !important;
    padding-left: 10px;
  }
  .toast-custom .toast-close:hover {
    color: #4b5563 !important; /* Tailwind gray-600 */
  }
</style>

<script>
  window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
      return;
    }

    @if(session('success'))
      Toastify({
        text: "{{ session('success') }}",
        duration: 5000,
        close: true,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        avatar: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='12' r='12' fill='%2310B981'/%3E%3Cpath d='M17 8L10 15L7 12' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E",
        style: {
          background: "#ffffff",
          color: "#374151",
          borderRadius: "12px",
          boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
          padding: "16px 20px",
          fontFamily: "'Instrument Sans', sans-serif",
          fontWeight: "500",
          border: "1px solid #f3f4f6",
          display: "flex",
          alignItems: "center",
          gap: "12px",
        },
        className: "toast-custom",
      }).showToast();
    @endif

    @if(session('error'))
      Toastify({
        text: "{{ session('error') }}",
        duration: 5000,
        close: true,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        avatar: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='12' r='12' fill='%23EF4444'/%3E%3Cpath d='M16 8L8 16M8 8L16 16' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E",
        style: {
          background: "#ffffff",
          color: "#374151",
          borderRadius: "12px",
          boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
          padding: "16px 20px",
          fontFamily: "'Instrument Sans', sans-serif",
          fontWeight: "500",
          border: "1px solid #f3f4f6",
          display: "flex",
          alignItems: "center",
          gap: "12px",
        },
        className: "toast-custom",
      }).showToast();
    @endif
    
    @if(session('warning'))
      Toastify({
        text: "{{ session('warning') }}",
        duration: 5000,
        close: true,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        avatar: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='12' r='12' fill='%23F59E0B'/%3E%3Cpath d='M12 8V12M12 16H12.01' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E",
        style: {
          background: "#ffffff",
          color: "#374151",
          borderRadius: "12px",
          boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
          padding: "16px 20px",
          fontFamily: "'Instrument Sans', sans-serif",
          fontWeight: "500",
          border: "1px solid #f3f4f6",
          display: "flex",
          alignItems: "center",
          gap: "12px",
        },
        className: "toast-custom",
      }).showToast();
    @endif

    @if(session('info'))
      Toastify({
        text: "{{ session('info') }}",
        duration: 5000,
        close: true,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        avatar: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='12' r='12' fill='%233B82F6'/%3E%3Cpath d='M12 16V12M12 8H12.01' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E",
        style: {
          background: "#ffffff",
          color: "#374151",
          borderRadius: "12px",
          boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
          padding: "16px 20px",
          fontFamily: "'Instrument Sans', sans-serif",
          fontWeight: "500",
          border: "1px solid #f3f4f6",
          display: "flex",
          alignItems: "center",
          gap: "12px",
        },
        className: "toast-custom",
      }).showToast();
    @endif
  });
</script>
