(function () {
    "use strict";

    const manager = document.querySelector("[data-backup-manager]");
    const status = document.getElementById("backup-operation-status");
    const statusText = status?.querySelector("[data-backup-status-text]");
    const statusSpinner = status?.querySelector("[data-backup-status-spinner]");

    if (!manager || !status || !statusText) {
        return;
    }

    const controls = Array.from(manager.querySelectorAll("[data-backup-control]"));

    const isBackupForm = (form) => form instanceof HTMLFormElement && form.hasAttribute("data-backup-operation");

    const setControlsLocked = (locked) => {
        controls.forEach((control) => {
            if (locked) {
                control.dataset.backupWasDisabled = control.disabled ? "1" : "0";
                control.disabled = true;
                control.classList.add("disabled");
                control.setAttribute("aria-disabled", "true");
                return;
            }

            const wasDisabled = control.dataset.backupWasDisabled === "1";
            control.disabled = wasDisabled;
            control.classList.toggle("disabled", wasDisabled);
            if (wasDisabled) {
                control.setAttribute("aria-disabled", "true");
            } else {
                control.removeAttribute("aria-disabled");
            }
            delete control.dataset.backupWasDisabled;
        });
    };

    const showStatus = (variant, message, spinning) => {
        status.classList.remove("d-none", "alert-info", "alert-success", "alert-danger");
        status.classList.add("d-flex", `alert-${variant}`);
        statusText.textContent = message;
        statusSpinner?.classList.toggle("d-none", !spinning);
    };

    document.addEventListener("kirpi:form.start", (event) => {
        const form = event.detail?.form;
        if (!isBackupForm(form)) {
            return;
        }

        const operation = form.dataset.backupOperation || "default";
        const message = status.dataset[`message${operation.charAt(0).toUpperCase()}${operation.slice(1)}`]
            || status.dataset.messageDefault;

        setControlsLocked(true);
        showStatus("info", message, true);
        status.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });

    document.addEventListener("kirpi:form.success", (event) => {
        const form = event.detail?.form;
        const result = event.detail?.result || {};
        if (!isBackupForm(form) || result.status !== "success") {
            return;
        }

        showStatus("success", result.message || status.dataset.messageDefault, false);
    });

    document.addEventListener("kirpi:form.error", (event) => {
        const form = event.detail?.form;
        const result = event.detail?.result || {};
        if (!isBackupForm(form)) {
            return;
        }

        showStatus("danger", result.message || status.dataset.messageFailed, false);
    });

    document.addEventListener("kirpi:form.complete", (event) => {
        if (!isBackupForm(event.detail?.form)) {
            return;
        }

        setControlsLocked(false);
    });
})();
