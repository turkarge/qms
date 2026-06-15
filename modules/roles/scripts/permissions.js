document.addEventListener("DOMContentLoaded", function () {
    const permissionsForm = document.getElementById("roles-permissions-form");
    const selectAllButton = document.getElementById("roles-permissions-select-all");
    const clearAllButton = document.getElementById("roles-permissions-clear-all");

    if (!permissionsForm) {
        return;
    }

    function getPermissionCheckboxes(scope) {
        return Array.from(
            (scope || document).querySelectorAll('input[name="permission_slugs[]"]:not(:disabled)')
        );
    }

    function setCheckboxGroupState(scope, checked) {
        getPermissionCheckboxes(scope).forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    function syncGroupToggle(group) {
        if (!group) {
            return;
        }

        const groupContainer = document.querySelector(`[data-permission-group="${group}"]`);
        const groupToggle = document.querySelector(`.roles-permissions-group-toggle[data-group="${group}"]`);

        if (!groupContainer || !groupToggle) {
            return;
        }

        const checkboxes = getPermissionCheckboxes(groupContainer);
        if (checkboxes.length === 0) {
            groupToggle.checked = false;
            return;
        }

        groupToggle.checked = checkboxes.every(function (checkbox) {
            return checkbox.checked;
        });
    }

    document.querySelectorAll(".roles-permissions-group-toggle").forEach(function (toggle) {
        syncGroupToggle(toggle.dataset.group);
    });

    if (selectAllButton) {
        selectAllButton.addEventListener("click", function () {
            setCheckboxGroupState(document, true);
        });
    }

    if (clearAllButton) {
        clearAllButton.addEventListener("click", function () {
            setCheckboxGroupState(document, false);
        });
    }

    document.addEventListener("change", function (event) {
        const groupToggle = event.target.closest(".roles-permissions-group-toggle");
        const permissionCheckbox = event.target.closest('input[name="permission_slugs[]"]');

        if (!groupToggle && !permissionCheckbox) {
            return;
        }

        if (groupToggle) {
            const group = groupToggle.dataset.group;
            if (!group) {
                return;
            }

            const groupContainer = document.querySelector(`[data-permission-group="${group}"]`);
            if (!groupContainer) {
                return;
            }

            setCheckboxGroupState(groupContainer, groupToggle.checked);
            return;
        }

        const groupContainer = permissionCheckbox.closest("[data-permission-group]");
        if (!groupContainer) {
            return;
        }

        syncGroupToggle(groupContainer.dataset.permissionGroup);
    });
});
