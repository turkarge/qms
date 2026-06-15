(function () {
    "use strict";

    const form = document.getElementById("lock-pin-form");
    if (!form) return;

    const inputs = Array.from(form.querySelectorAll("[data-lock-pin-digit]"));
    const pinValue = form.querySelector("[data-lock-pin-value]");
    const status = form.querySelector("[data-lock-pin-status]");
    let submitting = false;

    const sanitize = (value) => String(value || "").replace(/\D/g, "");
    const currentPin = () => inputs.map((input) => sanitize(input.value).slice(-1)).join("");

    const setDisabled = (disabled) => {
        inputs.forEach((input) => { input.disabled = disabled; });
        form.classList.toggle("is-submitting", disabled);
    };

    const clearPin = () => {
        inputs.forEach((input) => { input.value = ""; });
        if (pinValue) pinValue.value = "";
        if (status) status.textContent = "";
        inputs[0]?.focus();
    };

    const syncAndSubmit = () => {
        const pin = currentPin();
        if (pinValue) pinValue.value = pin;
        if (pin.length !== inputs.length || submitting) return;

        submitting = true;
        setDisabled(true);
        if (status) status.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>';
        form.requestSubmit();
    };

    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            input.value = sanitize(input.value).slice(-1);
            if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
            syncAndSubmit();
        });

        input.addEventListener("keydown", (event) => {
            if (event.key === "Backspace" && !input.value && index > 0) {
                inputs[index - 1].value = "";
                inputs[index - 1].focus();
                event.preventDefault();
            }
            if (event.key === "ArrowLeft" && index > 0) inputs[index - 1].focus();
            if (event.key === "ArrowRight" && index < inputs.length - 1) inputs[index + 1].focus();
        });

        input.addEventListener("focus", () => input.select());
    });

    form.addEventListener("paste", (event) => {
        const digits = sanitize(event.clipboardData?.getData("text")).slice(0, inputs.length);
        if (!digits) return;

        event.preventDefault();
        inputs.forEach((input, index) => { input.value = digits[index] || ""; });
        inputs[Math.min(digits.length, inputs.length) - 1]?.focus();
        syncAndSubmit();
    });

    form.addEventListener("submit", (event) => {
        const pin = currentPin();
        if (pinValue) pinValue.value = pin;
        if (pin.length !== inputs.length) {
            event.preventDefault();
            submitting = false;
            setDisabled(false);
            inputs.find((input) => !input.value)?.focus();
        }
    }, true);

    document.addEventListener("kirpi:form.error", (event) => {
        if (event.detail?.form !== form) return;
        submitting = false;
        setDisabled(false);
        clearPin();
    });

    document.addEventListener("kirpi:form.complete", (event) => {
        if (event.detail?.form !== form || event.detail?.result?.status === "success") return;
        submitting = false;
        setDisabled(false);
    });
}());
