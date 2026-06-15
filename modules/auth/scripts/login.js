document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("login-form");
  const alertArea = document.getElementById("login-alert-area");
  const submitButton = document.getElementById("login-submit-button");
  const togglePasswordButton = document.getElementById("toggle-login-password");
  const passwordInput = document.getElementById("login-password");

  if (togglePasswordButton && passwordInput) {
    togglePasswordButton.addEventListener("click", function (event) {
      event.preventDefault();

      const isPassword = passwordInput.getAttribute("type") === "password";
      passwordInput.setAttribute("type", isPassword ? "text" : "password");
      this.setAttribute("aria-pressed", isPassword ? "true" : "false");

      this.innerHTML = isPassword
        ? '<i class="ti ti-eye-off" aria-hidden="true"></i>'
        : '<i class="ti ti-eye" aria-hidden="true"></i>';
    });
  }

  if (!loginForm || !alertArea) {
    return;
  }

  function showAlert(message, type = "danger") {
    alertArea.innerHTML = `
            <div class="alert alert-${type}" role="alert">
                ${message}
            </div>
        `;
  }

  document.addEventListener("kirpi:form.success", function (event) {
    if (event.detail.form !== loginForm) {
      return;
    }

    const result = event.detail.result;

    if (result.status === "success") {
      showAlert(result.message || "Giriş başarılı.", "success");

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerText = "Yönlendiriliyor...";
      }

      return;
    }

    showAlert(result.message || "Bir hata oluştu.", "danger");
  });

  loginForm.addEventListener("submit", function () {
    alertArea.innerHTML = "";

    if (submitButton) {
      submitButton.innerText = "Giriş Yapılıyor...";
    }
  });
});
