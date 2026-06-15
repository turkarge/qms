(function () {
    "use strict";

    const KirpiCore = {
        baseUrl: window.location.origin,
        csrfToken: null,
        modalInstance: null,
        secondaryModalInstance: null,
        confirmCallback: null,
        lastTriggerElement: null,

        init() {
            this.bootstrapGlobals();
            this.initTheme();
            this.initLayoutWidth();
            this.initToastr();
            this.showFlashMessage();
            this.showPendingToast();
            this.initMainModal();
            this.initSecondaryModal();
            this.initDropdowns();
            this.bindNotificationDropdown();
            this.bindModalTriggers();
            this.bindConfirmTriggers();
            this.bindAjaxForms();
            this.initDocumentManager();
            this.initAiLauncher();
            this.bindAiProviderTests();
            this.bindAiDebugCopy();
        },

        bootstrapGlobals() {
            if (window.KIRPI_CONFIG) {
                this.baseUrl = window.KIRPI_CONFIG.baseUrl || this.baseUrl;
                this.csrfToken = window.KIRPI_CONFIG.csrfToken || null;
                this.flashMessage = window.KIRPI_CONFIG.flashMessage || null;
            }
        },

        initTheme() {
            const root = document.documentElement;
            const iconMap = {
                light: "ti ti-moon-stars fs-2 js-theme-toggle-icon",
                dark: "ti ti-sun-high fs-2 js-theme-toggle-icon"
            };
            const getSystemTheme = () => window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            const getPreference = () => root.getAttribute("data-kirpi-theme-preference") || "system";

            const applyThemePreference = (preference) => {
                const safePreference = ["light", "dark", "system"].includes(preference) ? preference : "system";
                const safeTheme = safePreference === "system" ? getSystemTheme() : safePreference;
                root.setAttribute("data-bs-theme", safeTheme);
                root.setAttribute("data-kirpi-theme", safeTheme);
                root.setAttribute("data-kirpi-theme-preference", safePreference);
                if (document.body) {
                    document.body.setAttribute("data-bs-theme", safeTheme);
                    document.body.setAttribute("data-kirpi-theme", safeTheme);
                    document.body.setAttribute("data-kirpi-theme-preference", safePreference);
                }

                document.querySelectorAll(".js-theme-toggle").forEach((button) => {
                    const icon = button.querySelector(".js-theme-toggle-icon");
                    if (icon) {
                        icon.className = iconMap[safeTheme];
                    }
                    button.setAttribute("aria-label", safeTheme === "dark" ? "Açık temaya geç" : "Koyu temaya geç");
                    button.setAttribute("title", safeTheme === "dark" ? "Açık temaya geç" : "Koyu temaya geç");
                });

                document.querySelectorAll("[data-theme-choice]").forEach((button) => {
                    button.classList.toggle("active", button.dataset.themeChoice === safePreference);
                });

                try {
                    window.localStorage.setItem("kirpi_theme_preference", safePreference);
                } catch (error) {
                    console.warn("Tema tercihi kaydedilemedi:", error);
                }

                document.dispatchEvent(new CustomEvent("kirpi:theme.changed", {
                    detail: {
                        preference: safePreference,
                        theme: safeTheme
                    }
                }));
            };

            applyThemePreference(getPreference());

            document.addEventListener("click", (event) => {
                const toggle = event.target.closest(".js-theme-toggle");
                if (toggle) {
                    event.preventDefault();
                    const nextPreference = (root.getAttribute("data-kirpi-theme") || "light") === "dark" ? "light" : "dark";
                    applyThemePreference(nextPreference);
                }

                const choice = event.target.closest("[data-theme-choice]");
                if (choice) {
                    event.preventDefault();
                    applyThemePreference(choice.dataset.themeChoice || "system");
                }
            });

            if (window.matchMedia) {
                const media = window.matchMedia("(prefers-color-scheme: dark)");
                const syncSystemTheme = () => {
                    if (getPreference() === "system") {
                        applyThemePreference("system");
                    }
                };
                if (typeof media.addEventListener === "function") {
                    media.addEventListener("change", syncSystemTheme);
                } else if (typeof media.addListener === "function") {
                    media.addListener(syncSystemTheme);
                }
            }
        },

        initLayoutWidth() {
            const root = document.documentElement;
            const iconMap = {
                boxed: "ti ti-arrows-maximize fs-2 js-layout-toggle-icon",
                fluid: "ti ti-arrows-minimize fs-2 js-layout-toggle-icon"
            };

            const applyLayout = (layout) => {
                const safeLayout = layout === "fluid" ? "fluid" : "boxed";
                root.setAttribute("data-kirpi-layout", safeLayout);
                if (document.body) {
                    document.body.setAttribute("data-kirpi-layout", safeLayout);
                }

                document.querySelectorAll(".js-layout-toggle").forEach((button) => {
                    const icon = button.querySelector(".js-layout-toggle-icon");
                    if (icon) {
                        icon.className = iconMap[safeLayout];
                    }
                    const label = button.querySelector("span");
                    if (label) {
                        label.textContent = safeLayout === "fluid" ? "Dar görünüm" : "Geniş görünüm";
                    }
                    button.setAttribute("aria-label", safeLayout === "fluid" ? "Dar görünüm" : "Geniş görünüm");
                    button.setAttribute("title", safeLayout === "fluid" ? "Dar görünüm" : "Geniş görünüm");
                });

                try {
                    window.localStorage.setItem("kirpi_layout_width", safeLayout);
                } catch (error) {
                    console.warn("Görünüm tercihi kaydedilemedi:", error);
                }
            };

            applyLayout(root.getAttribute("data-kirpi-layout") || "boxed");

            document.addEventListener("click", (event) => {
                const toggle = event.target.closest(".js-layout-toggle");
                if (toggle) {
                    event.preventDefault();
                    applyLayout((root.getAttribute("data-kirpi-layout") || "boxed") === "fluid" ? "boxed" : "fluid");
                }
            });
        },

        initToastr() {
            if (!window.toastr) {
                return;
            }

            toastr.options = {
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                positionClass: "toast-top-right",
                timeOut: 3500,
                extendedTimeOut: 1500
            };
        },

        showFlashMessage() {
            if (!this.flashMessage || !this.flashMessage.message) {
                return;
            }

            const flashTypeMap = {
                success: "success",
                danger: "error",
                error: "error",
                warning: "warning",
                info: "info"
            };

            this.toast(
                this.flashMessage.message,
                flashTypeMap[this.flashMessage.type] || "info"
            );

            this.flashMessage = null;
        },

        showPendingToast() {
            try {
                const raw = window.sessionStorage.getItem("kirpi_pending_toast");
                if (!raw) {
                    return;
                }

                window.sessionStorage.removeItem("kirpi_pending_toast");

                const toast = JSON.parse(raw);
                if (!toast || !toast.message) {
                    return;
                }

                this.toast(toast.message, toast.type || "info");
            } catch (error) {
                console.warn("Pending toast okunamadı:", error);
            }
        },

        persistPendingToast(message, type = "info") {
            if (!message) {
                return;
            }

            try {
                window.sessionStorage.setItem("kirpi_pending_toast", JSON.stringify({
                    message: message,
                    type: type
                }));
            } catch (error) {
                console.warn("Pending toast kaydedilemedi:", error);
            }
        },

        initMainModal() {
            const modalEl = document.getElementById("main-modal");
            if (!modalEl) return;

            if (window.bootstrap && bootstrap.Modal) {
                this.modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);

                modalEl.addEventListener("hidden.bs.modal", () => {
                    if (this.lastTriggerElement && typeof this.lastTriggerElement.focus === "function") {
                        this.lastTriggerElement.focus();
                    }
                });
            } else {
                console.warn("Bootstrap modal API bulunamadı: main-modal");
            }
        },

        initSecondaryModal() {
            const modalEl = document.getElementById("secondary-modal");
            if (!modalEl) return;

            if (window.bootstrap && bootstrap.Modal) {
                this.secondaryModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);

                modalEl.addEventListener("hidden.bs.modal", () => {
                    if (this.lastTriggerElement && typeof this.lastTriggerElement.focus === "function") {
                        this.lastTriggerElement.focus();
                    }
                });
            } else {
                console.warn("Bootstrap modal API bulunamadı: secondary-modal");
            }
        },

        initDropdowns() {
            if (!(window.bootstrap && bootstrap.Dropdown)) {
                console.warn("Bootstrap Dropdown API bulunamadı.");
                return;
            }

            const dropdownToggles = Array.from(document.querySelectorAll('[data-bs-toggle="dropdown"]'));

            const hideOtherDropdowns = (currentToggle = null) => {
                const currentContainer = currentToggle ? currentToggle.closest(".dropdown, .dropend") : null;

                dropdownToggles.forEach((toggle) => {
                    if (toggle === currentToggle) {
                        return;
                    }

                    const otherContainer = toggle.closest(".dropdown, .dropend");
                    const isSameBranch = currentContainer && otherContainer
                        && (otherContainer.contains(currentContainer) || currentContainer.contains(otherContainer));

                    if (isSameBranch) {
                        return;
                    }

                    bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                });
            };

            dropdownToggles.forEach((toggle) => {
                const instance = bootstrap.Dropdown.getOrCreateInstance(toggle);

                toggle.addEventListener("click", (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    hideOtherDropdowns(toggle);
                    instance.toggle();
                });
            });

            document.addEventListener("click", (event) => {
                const navLink = event.target.closest('#navbar-menu a[href]:not([href="#"])');

                if (!event.target.closest(".dropdown-menu")) {
                    hideOtherDropdowns(null);
                }

                if (!navLink) {
                    return;
                }

                hideOtherDropdowns(null);

                const collapseEl = document.getElementById("navbar-menu");
                if (collapseEl && collapseEl.classList.contains("show") && window.bootstrap && bootstrap.Collapse) {
                    bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).hide();
                }
            });
        },

        async get(url, options = {}) {
            return await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    ...options.headers
                }
            });
        },

        async post(url, data = {}, options = {}) {
            const formData = new FormData();

            Object.keys(data).forEach((key) => {
                formData.append(key, data[key]);
            });

            if (this.csrfToken && !formData.has("csrf_token")) {
                formData.append("csrf_token", this.csrfToken);
            }

            return await fetch(url, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    ...options.headers
                }
            });
        },

        showLoading(target) {
            if (!target) return;

            target.innerHTML = `
                <div class="kirpi-loading">
                    <div class="spinner-border" role="status"></div>
                </div>
            `;
        },

        closeModal(target = "main") {
            const modalId = target === "secondary" ? "secondary-modal" : "main-modal";
            const modalEl = document.getElementById(modalId);

            if (!modalEl) {
                return;
            }

            this.forceCloseModalElement(modalEl);
        },

        closeModalElement(modalEl) {
            if (!modalEl) {
                return;
            }

            try {
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                    instance.hide();
                }
            } catch (error) {
                console.warn("Bootstrap modal hide hatası:", error);
            }

            this.forceCloseModalElement(modalEl);
        },

        forceCloseModalElement(modalEl) {
            if (!modalEl) {
                return;
            }

            try {
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) {
                        instance.hide();
                    }
                }
            } catch (error) {
                console.warn("Bootstrap modal hide hatası:", error);
            }

            // fallback: bootstrap hide nazlanırsa manuel temizle
            setTimeout(() => {
                modalEl.classList.remove("show");
                modalEl.style.display = "none";
                modalEl.removeAttribute("aria-modal");
                modalEl.setAttribute("aria-hidden", "true");

                document.body.classList.remove("modal-open");
                document.body.style.removeProperty("padding-right");
                document.body.style.removeProperty("overflow");

                document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
                    backdrop.remove();
                });
            }, 150);
        },

        async openModal(url, title = "", size = "modal-lg", target = "main") {
            const modalId = target === "secondary" ? "secondary-modal" : "main-modal";
            const contentId = target === "secondary" ? "secondary-modal-content" : "main-modal-content";

            const modalEl = document.getElementById(modalId);
            const contentEl = document.getElementById(contentId);

            if (!modalEl || !contentEl) return;

            const dialog = modalEl.querySelector(".modal-dialog");
            if (dialog) {
                dialog.className = "modal-dialog modal-dialog-centered " + size;
            }

            this.showLoading(contentEl);

            const instance = target === "secondary"
                ? this.secondaryModalInstance
                : this.modalInstance;

            if (instance && typeof instance.show === "function") {
                instance.show();
            } else {
                console.warn("Modal instance oluşturulamadı:", target);
            }

            try {
                const response = await this.get(url);
                const html = await response.text();
                contentEl.innerHTML = html;
            } catch (error) {
                contentEl.innerHTML = `
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            İçerik yüklenirken bir hata oluştu.
                        </div>
                    </div>
                `;
            }
        },

        bindModalTriggers() {
            document.addEventListener("click", (event) => {
                const trigger = event.target.closest(".btn-modal-trigger");
                if (!trigger) return;

                event.preventDefault();

                const url = trigger.dataset.url;
                const size = trigger.dataset.size || "modal-lg";
                const target = trigger.dataset.target || "main";

                if (!url) return;

                this.lastTriggerElement = trigger;
                this.openModal(this.normalizeUrl(url), "", size, target);
            });
        },

        bindConfirmTriggers() {
            const confirmModalEl = document.getElementById("confirm-modal");
            const confirmYesBtn = document.getElementById("confirm-modal-yes");
            const confirmText = document.getElementById("confirm-modal-text");

            if (!confirmModalEl || !confirmYesBtn || !window.bootstrap) {
                return;
            }

            const confirmModal = new bootstrap.Modal(confirmModalEl);

            document.addEventListener("click", (event) => {
                const trigger = event.target.closest("[data-confirm]");
                if (!trigger) return;

                event.preventDefault();

                if (trigger.classList.contains("disabled") || trigger.getAttribute("aria-disabled") === "true") {
                    return;
                }

                const message = trigger.dataset.confirm || "Emin misiniz?";
                confirmText.textContent = message;

                this.confirmCallback = () => {
                    const href = trigger.getAttribute("href");
                    const formId = trigger.dataset.form;

                    if (formId) {
                        const form = document.getElementById(formId);
                        if (form) {
                            if (form.matches("form[data-ajax='true']")) {
                                this.submitAjaxForm(form, trigger);
                                return;
                            }

                            if (typeof form.requestSubmit === "function") {
                                form.requestSubmit();
                            } else {
                                form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
                            }
                            return;
                        }
                    }

                    if (href && href !== "#") {
                        window.location.href = href;
                    }
                };

                confirmModal.show();
            });

            confirmYesBtn.addEventListener("click", () => {
                if (typeof this.confirmCallback === "function") {
                    this.confirmCallback();
                }
                confirmModal.hide();
            });
        },

        async submitAjaxForm(form, trigger = null) {
            if (!form || !form.matches("form[data-ajax='true']")) {
                return;
            }

            const submitButton = trigger || form.querySelector("[type='submit']");
            if (submitButton) {
                submitButton.disabled = true;
            }

            let result = null;

            document.dispatchEvent(new CustomEvent("kirpi:form.start", {
                detail: {
                    form: form,
                    trigger: submitButton
                }
            }));

            try {
                const formData = new FormData(form);

                if (this.csrfToken && !formData.has("csrf_token")) {
                    formData.append("csrf_token", this.csrfToken);
                }

                const response = await fetch(form.action, {
                    method: form.method || "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                });

                const responseTextRaw = await response.text();
                const responseText = responseTextRaw.replace(/^\uFEFF/, "");

                if (!response.ok && responseText.trim() === "") {
                    this.toast(`Islem basarisiz oldu (HTTP ${response.status}).`, "error");
                    document.dispatchEvent(new CustomEvent("kirpi:form.error", {
                        detail: {
                            form: form,
                            trigger: submitButton,
                            result: null,
                            error: new Error(`HTTP ${response.status}`)
                        }
                    }));
                    return;
                }

                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    if (!response.ok) {
                        this.toast(`Islem basarisiz oldu (HTTP ${response.status}).`, "error");
                    } else {
                        this.toast("Sunucu beklenmeyen bir yanit dondurdu.", "error");
                    }
                    document.dispatchEvent(new CustomEvent("kirpi:form.error", {
                        detail: {
                            form: form,
                            trigger: submitButton,
                            result: null,
                            error: parseError
                        }
                    }));
                    return;
                }

                if (result.message) {
                    this.toast(result.message, result.status || "info");
                }

                if (result.status === "success" && form.dataset.closeModal === "true") {
                    this.closeModalElement(form.closest(".modal"));
                }

                document.dispatchEvent(new CustomEvent("kirpi:form.success", {
                    detail: {
                        form: form,
                        result: result
                    }
                }));

                if (result.status !== "success") {
                    document.dispatchEvent(new CustomEvent("kirpi:form.error", {
                        detail: {
                            form: form,
                            trigger: submitButton,
                            result: result,
                            error: null
                        }
                    }));
                }

                if (result.reload_page) {
                    if (result.message) {
                        this.persistPendingToast(result.message, result.status || "info");
                    }
                    window.location.reload();
                    return;
                }

                if (result.redirect) {
                    window.location.href = result.redirect;
                    return;
                }
            } catch (error) {
                console.error("AJAX form submit error:", error);
                this.toast("Islem sirasinda bir hata olustu.", "error");
                document.dispatchEvent(new CustomEvent("kirpi:form.error", {
                    detail: {
                        form: form,
                        trigger: submitButton,
                        result: result,
                        error: error
                    }
                }));
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }

                document.dispatchEvent(new CustomEvent("kirpi:form.complete", {
                    detail: {
                        form: form,
                        trigger: submitButton,
                        result: result
                    }
                }));
            }
        },

        getNotificationUnreadCount() {
            const bell = document.querySelector(".js-notification-bell");
            if (!bell) {
                return 0;
            }

            const value = parseInt(bell.dataset.unreadCount || "0", 10);
            return Number.isNaN(value) ? 0 : value;
        },

        setNotificationUnreadCount(count) {
            const next = Math.max(0, parseInt(String(count), 10) || 0);
            const bells = document.querySelectorAll(".js-notification-bell");

            bells.forEach((bell) => {
                bell.dataset.unreadCount = String(next);
                const existingDot = bell.querySelector(".js-notification-dot");
                if (next > 0) {
                    if (!existingDot) {
                        const dot = document.createElement("span");
                        dot.className = "badge badge-sm bg-red text-red-fg ms-1 js-notification-dot js-notification-count";
                        bell.appendChild(dot);
                    }
                    const countBadge = bell.querySelector(".js-notification-count");
                    if (countBadge) {
                        countBadge.textContent = next > 99 ? "99+" : String(next);
                    }
                } else if (existingDot) {
                    existingDot.remove();
                }
            });

            document.querySelectorAll(".js-notification-mark-all").forEach((button) => {
                button.classList.toggle("d-none", next === 0);
            });
        },

        decreaseNotificationUnreadCount(step = 1) {
            const current = this.getNotificationUnreadCount();
            this.setNotificationUnreadCount(current - step);
        },

        bindNotificationDropdown() {
            document.addEventListener("click", async (event) => {
                const markAllButton = event.target.closest(".js-notification-mark-all");
                if (markAllButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (markAllButton.classList.contains("disabled")) return;
                    markAllButton.classList.add("disabled");
                    markAllButton.setAttribute("aria-disabled", "true");

                    try {
                        const response = await this.post(markAllButton.dataset.markReadUrl || "", {});
                        const result = await response.json();
                        if (!response.ok || result.status !== "success") {
                            throw new Error(result.message || "Bildirimler güncellenemedi.");
                        }

                        document.querySelectorAll(".js-notification-item").forEach((item) => {
                            this.markNotificationItemAsRead(item);
                        });
                        this.setNotificationUnreadCount(Number(result.unread_count || 0));
                        this.toast(result.message, "success");
                    } catch (error) {
                        this.toast(error.message || "Bildirimler güncellenemedi.", "error");
                    } finally {
                        markAllButton.classList.remove("disabled");
                        markAllButton.removeAttribute("aria-disabled");
                    }
                    return;
                }

                const markReadButton = event.target.closest(".js-notification-mark-read");
                const openTrigger = event.target.closest(".js-notification-open");
                if (!markReadButton && !openTrigger) {
                    return;
                }

                const item = event.target.closest(".js-notification-item");
                if (!item) return;

                const targetUrl = openTrigger?.getAttribute("href") || "";
                const shouldNavigate = Boolean(openTrigger && targetUrl);
                if (markReadButton || item.dataset.isUnread === "1") {
                    event.preventDefault();
                }
                if (markReadButton) {
                    event.stopPropagation();
                    if (markReadButton.classList.contains("disabled")) return;
                    markReadButton.classList.add("disabled");
                    markReadButton.setAttribute("aria-disabled", "true");
                }

                if (item.dataset.isUnread !== "1") {
                    if (shouldNavigate) window.location.href = targetUrl;
                    return;
                }

                try {
                    const response = await this.post(item.dataset.markReadUrl || "", {
                        id: String(parseInt(item.dataset.notificationId || "0", 10))
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== "success") {
                        throw new Error(result.message || "Bildirim güncellenemedi.");
                    }

                    this.markNotificationItemAsRead(item);
                    this.setNotificationUnreadCount(Number(result.unread_count ?? this.getNotificationUnreadCount() - 1));
                    if (markReadButton) this.toast(result.message, "success");
                } catch (error) {
                    console.warn("Notification mark-read error:", error);
                    if (markReadButton) this.toast(error.message || "Bildirim güncellenemedi.", "error");
                } finally {
                    if (markReadButton) {
                        markReadButton.classList.remove("disabled");
                        markReadButton.removeAttribute("aria-disabled");
                    }
                    if (shouldNavigate) window.location.href = targetUrl;
                }
            });

            document.addEventListener("kirpi:form.success", (event) => {
                const form = event.detail?.form;
                const result = event.detail?.result || {};

                if (!form || result.status !== "success") {
                    return;
                }

                if (form.classList.contains("notifications-mark-read-form")) {
                    this.decreaseNotificationUnreadCount(1);
                }

                if (form.id === "notifications-mark-all-read-form") {
                    document.querySelectorAll(".js-notification-item").forEach((item) => {
                        this.markNotificationItemAsRead(item);
                    });
                    this.setNotificationUnreadCount(Number(result.unread_count || 0));
                }
            });
        },

        markNotificationItemAsRead(item) {
            if (!item) return;
            item.dataset.isUnread = "0";
            item.classList.remove("is-unread");
            const dot = item.querySelector(".js-notification-item-dot");
            if (dot) {
                dot.classList.remove("status-dot-animated", "bg-red");
                dot.classList.add("bg-secondary");
            }
            item.querySelector(".js-notification-mark-read")?.remove();
        },

        bindAjaxForms() {
            document.addEventListener("submit", async (event) => {
                const rawTarget = event.target;
                const form = rawTarget instanceof HTMLFormElement
                    ? rawTarget
                    : rawTarget.closest("form[data-ajax='true']");

                if (!form || !form.matches("form[data-ajax='true']")) {
                    return;
                }

                event.preventDefault();
                await this.submitAjaxForm(form, event.submitter || null);
            }, true);
        },

        initDocumentManager() {
            const manager = document.querySelector("[data-document-manager]");
            if (!manager) {
                return;
            }

            const collection = manager.querySelector("[data-document-collection]");
            const viewButtons = manager.querySelectorAll("[data-document-view]");
            const selectionBar = manager.querySelector("[data-document-selection-bar]");
            const selectionCount = manager.querySelector("[data-document-selection-count]");
            const selectedIdsInput = manager.querySelector("[data-document-selected-ids]");
            const checkboxes = Array.from(manager.querySelectorAll("[data-document-select]"));
            const selectedLabel = "dosya secildi";

            const applyView = (view) => {
                const safeView = view === "list" ? "list" : "grid";
                if (collection) {
                    collection.dataset.view = safeView;
                }
                viewButtons.forEach((button) => {
                    button.classList.toggle("active", button.dataset.documentView === safeView);
                });
                try {
                    localStorage.setItem("kirpi.documents.view", safeView);
                } catch (error) {
                    console.warn("Document view preference error:", error);
                }
            };

            let initialView = "grid";
            try {
                initialView = localStorage.getItem("kirpi.documents.view") || "grid";
            } catch (error) {
                initialView = "grid";
            }
            applyView(initialView);
            viewButtons.forEach((button) => button.addEventListener("click", () => applyView(button.dataset.documentView)));

            const selectedIds = () => checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
            const syncSelection = () => {
                const ids = selectedIds();
                if (selectionBar) {
                    selectionBar.hidden = ids.length === 0;
                }
                if (selectionCount) {
                    selectionCount.textContent = `${ids.length} ${selectedLabel}`;
                }
                if (selectedIdsInput) {
                    selectedIdsInput.value = JSON.stringify(ids);
                }
                checkboxes.forEach((checkbox) => {
                    const item = checkbox.closest("[data-document-item]");
                    if (item) {
                        item.classList.toggle("is-selected", checkbox.checked);
                    }
                });
            };
            checkboxes.forEach((checkbox) => checkbox.addEventListener("change", syncSelection));

            const clearSelection = manager.querySelector("[data-document-clear-selection]");
            if (clearSelection) {
                clearSelection.addEventListener("click", () => {
                    checkboxes.forEach((checkbox) => { checkbox.checked = false; });
                    syncSelection();
                });
            }

            const downloadSelected = manager.querySelector("[data-document-download-selected]");
            if (downloadSelected) {
                downloadSelected.addEventListener("click", () => {
                    const selectedItems = Array.from(manager.querySelectorAll("[data-document-item]")).filter((item) => {
                        const checkbox = item.querySelector("[data-document-select]");
                        return checkbox && checkbox.checked;
                    });
                    selectedItems.forEach((item, index) => {
                        const url = item.dataset.downloadUrl;
                        if (!url) return;
                        setTimeout(() => {
                            const link = document.createElement("a");
                            link.href = url;
                            link.style.display = "none";
                            document.body.appendChild(link);
                            link.click();
                            link.remove();
                        }, index * 300);
                    });
                });
            }

        },

        formatBytes(bytes) {
            const value = Number(bytes) || 0;
            if (value >= 1024 * 1024) return `${(value / 1024 / 1024).toFixed(2)} MB`;
            if (value >= 1024) return `${(value / 1024).toFixed(2)} KB`;
            return `${value} B`;
        },

        initAiLauncher() {
            const launcher = document.querySelector("[data-ai-launcher]");
            if (!launcher) {
                return;
            }

            const toggle = launcher.querySelector("[data-ai-launcher-toggle]");
            const close = launcher.querySelector("[data-ai-launcher-close]");
            const panel = launcher.querySelector("[data-ai-launcher-panel]");

            if (!toggle || !panel) {
                return;
            }

            const setOpen = (isOpen, compact = false) => {
                launcher.classList.toggle("is-open", isOpen);
                launcher.classList.toggle("is-minimized", !isOpen && compact);
                toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
                panel.setAttribute("aria-hidden", isOpen ? "false" : "true");
            };

            const rememberClosed = () => {
                try {
                    window.localStorage.setItem("kirpi_ai_launcher_minimized", "1");
                } catch (error) {
                    console.warn("AI launcher tercihi kaydedilemedi:", error);
                }
            };

            try {
                setOpen(false, window.localStorage.getItem("kirpi_ai_launcher_minimized") === "1");
            } catch (error) {
                setOpen(false);
            }

            toggle.addEventListener("click", (event) => {
                event.preventDefault();
                const nextOpen = !launcher.classList.contains("is-open");
                setOpen(nextOpen);
                if (!nextOpen) {
                    rememberClosed();
                }
            });

            if (close) {
                close.addEventListener("click", (event) => {
                    event.preventDefault();
                    setOpen(false, true);
                    rememberClosed();
                    toggle.focus();
                });
            }

            document.addEventListener("click", (event) => {
                if (!launcher.classList.contains("is-open") || launcher.contains(event.target)) {
                    return;
                }
                setOpen(false, true);
                rememberClosed();
            });

            document.addEventListener("keydown", (event) => {
                if (event.key !== "Escape" || !launcher.classList.contains("is-open")) {
                    return;
                }
                setOpen(false, true);
                rememberClosed();
                toggle.focus();
            });
        },

        bindAiProviderTests() {
            document.addEventListener("click", async (event) => {
                const trigger = event.target.closest(".js-ai-provider-test");
                if (!trigger) {
                    return;
                }

                event.preventDefault();

                const url = trigger.dataset.url || "";
                const adapterKey = trigger.dataset.adapterKey || "";
                if (!url || !adapterKey) {
                    return;
                }

                trigger.disabled = true;
                const originalHtml = trigger.innerHTML;
                trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Test ediliyor';

                try {
                    const response = await this.post(url, {
                        adapter_key: adapterKey
                    });
                    const responseText = (await response.text()).replace(/^\uFEFF/, "");
                    let result = null;
                    try {
                        result = JSON.parse(responseText);
                    } catch (error) {
                        this.toast(`Provider testi beklenmeyen yanıt döndürdü (HTTP ${response.status}).`, "error");
                        return;
                    }

                    if (result.message) {
                        this.toast(result.message, result.status || (response.ok ? "success" : "error"));
                    }
                } catch (error) {
                    console.warn("Provider test error:", error);
                    this.toast("Provider testi sırasında bir hata oluştu.", "error");
                } finally {
                    trigger.disabled = false;
                    trigger.innerHTML = originalHtml;
                }
            });
        },

        bindAiDebugCopy() {
            document.addEventListener("click", async (event) => {
                const trigger = event.target.closest(".js-ai-debug-copy");
                if (!trigger) {
                    return;
                }

                event.preventDefault();

                const targetId = trigger.dataset.debugTarget || "";
                const source = targetId ? document.getElementById(targetId) : null;
                const value = source ? (source.textContent || "").trim() : "";
                if (!value) {
                    this.toast("Debug JSON bulunamadi.", "warning");
                    return;
                }

                const originalHtml = trigger.innerHTML;
                trigger.disabled = true;

                try {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const textarea = document.createElement("textarea");
                        textarea.value = value;
                        textarea.setAttribute("readonly", "readonly");
                        textarea.style.position = "fixed";
                        textarea.style.left = "-9999px";
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand("copy");
                        textarea.remove();
                    }

                    this.toast("Debug JSON panoya kopyalandi.", "success");
                } catch (error) {
                    console.warn("Debug JSON copy error:", error);
                    this.toast("Debug JSON kopyalanamadi.", "error");
                } finally {
                    trigger.disabled = false;
                    trigger.innerHTML = originalHtml;
                }
            });
        },

        toast(message, type = "info") {
            if (window.toastr && typeof toastr[type] === "function") {
                try {
                    toastr[type](message);
                    return;
                } catch (error) {
                    console.warn("Toastr hatası:", error);
                }
            }

            alert(message);
        },

        normalizeUrl(url) {
            if (!url) return url;
            if (url.startsWith("http://") || url.startsWith("https://")) return url;
            if (url.startsWith("/")) return this.baseUrl + url;
            return this.baseUrl + "/" + url;
        }
    };

    window.KirpiCore = KirpiCore;

    document.addEventListener("DOMContentLoaded", function () {
        KirpiCore.init();
    });
})();


