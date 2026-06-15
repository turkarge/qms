(function () {
    "use strict";

    const manager = document.querySelector("[data-document-manager]");
    const inspector = manager?.querySelector("[data-document-inspector]");
    const items = Array.from(manager?.querySelectorAll("[data-document-item]") || []);

    const showDocumentDetails = (item) => {
        if (!inspector || !item) {
            return;
        }

        items.forEach((candidate) => candidate.classList.toggle("is-active", candidate === item));
        inspector.querySelector("[data-document-inspector-empty]")?.setAttribute("hidden", "hidden");
        inspector.querySelector("[data-document-inspector-content]")?.removeAttribute("hidden");

        const values = {
            name: item.dataset.documentName || "-",
            mime: item.dataset.documentMime || "-",
            type: item.dataset.documentType || "-",
            size: item.dataset.documentSize || "-",
            owner: item.dataset.documentOwner || "-",
            date: item.dataset.documentDate || "-",
            links: item.dataset.documentLinks || "-"
        };

        Object.entries(values).forEach(([key, value]) => {
            const target = inspector.querySelector(`[data-document-inspector-${key}]`);
            if (target) {
                target.textContent = value;
            }
        });

        const avatar = inspector.querySelector("[data-document-inspector-avatar]");
        if (avatar) {
            avatar.className = `avatar avatar-xl mb-3 bg-${item.dataset.documentTone || "secondary"}-lt`;
            avatar.innerHTML = `<i class="ti ${item.dataset.documentIcon || "ti-file"} fs-1"></i>`;
        }

        const download = inspector.querySelector("[data-document-inspector-download]");
        if (download) {
            download.href = item.dataset.downloadUrl || "#";
        }

        if (window.bootstrap?.Offcanvas) {
            window.bootstrap.Offcanvas.getOrCreateInstance(inspector).show();
        }
    };

    items.forEach((item) => {
        item.addEventListener("click", (event) => {
            if (event.target.closest("a, button, input, label, form")) {
                return;
            }
            showDocumentDetails(item);
        });
        item.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                showDocumentDetails(item);
            }
        });
    });

    const form = document.querySelector("[data-document-filepond-form]");
    const input = form?.querySelector("[data-document-filepond]");

    if (!form || !input || typeof window.FilePond === "undefined") {
        return;
    }

    const status = form.querySelector("[data-document-upload-status]");
    const submit = form.querySelector("[data-document-upload-submit]");
    const modalElement = document.getElementById("document-upload-modal");
    const maxFileSize = Number(input.dataset.maxFileSize || 0);
    const acceptedFileTypes = (input.dataset.acceptedFileTypes || "")
        .split(",")
        .map((value) => value.trim())
        .filter(Boolean);
    let completedUploads = 0;
    let uploadRunning = false;

    FilePond.registerPlugin(
        FilePondPluginFileValidateType,
        FilePondPluginFileValidateSize
    );

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const pond = FilePond.create(input, {
        allowMultiple: true,
        allowReorder: true,
        allowRevert: false,
        instantUpload: false,
        maxParallelUploads: 3,
        maxFileSize: maxFileSize || null,
        acceptedFileTypes,
        credits: false,
        labelIdle: '<span class="filepond--label-action">Dosya seçin</span> veya buraya sürükleyin',
        labelInvalidField: "Alan geçersiz dosyalar içeriyor",
        labelFileWaitingForSize: "Boyut hesaplanıyor",
        labelFileSizeNotAvailable: "Boyut kullanılamıyor",
        labelFileLoading: "Yükleniyor",
        labelFileLoadError: "Dosya yüklenemedi",
        labelFileProcessing: "Sunucuya aktarılıyor",
        labelFileProcessingComplete: "Yüklendi",
        labelFileProcessingAborted: "Yükleme iptal edildi",
        labelFileProcessingError: "Yükleme başarısız",
        labelFileProcessingRevertError: "Geri alma başarısız",
        labelFileRemoveError: "Dosya kaldırılamadı",
        labelTapToCancel: "İptal etmek için dokunun",
        labelTapToRetry: "Tekrar denemek için dokunun",
        labelTapToUndo: "Geri almak için dokunun",
        labelButtonRemoveItem: "Kaldır",
        labelButtonAbortItemLoad: "İptal",
        labelButtonRetryItemLoad: "Tekrar dene",
        labelButtonAbortItemProcessing: "İptal",
        labelButtonUndoItemProcessing: "Geri al",
        labelButtonRetryItemProcessing: "Tekrar dene",
        labelButtonProcessItem: "Yükle",
        labelMaxFileSizeExceeded: "Dosya çok büyük",
        labelMaxFileSize: "En fazla {filesize}",
        labelFileTypeNotAllowed: "Dosya türüne izin verilmiyor",
        fileValidateTypeLabelExpectedTypes: "İzin verilen türler: {allTypes}",
        server: {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                const payload = new FormData();
                payload.append("csrf_token", form.elements.csrf_token.value);
                payload.append("document_type", form.elements.document_type.value);
                payload.append("entity_type", form.elements.entity_type.value);
                payload.append("entity_id", form.elements.entity_id.value);
                payload.append("filepond", "1");
                payload.append("document_file", file, file.name);

                const request = new XMLHttpRequest();
                request.open("POST", form.action, true);
                request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                request.upload.onprogress = (event) => {
                    progress(event.lengthComputable, event.loaded, event.total);
                };
                request.onload = () => {
                    let response = null;
                    try {
                        response = JSON.parse((request.responseText || "").replace(/^\uFEFF/, ""));
                    } catch (parseError) {
                        error("Sunucu geçersiz bir yanıt döndürdü.");
                        return;
                    }

                    if (request.status >= 200 && request.status < 300 && response.status === "success") {
                        completedUploads += 1;
                        load(String(response.uploaded?.[0]?.id || file.name));
                        return;
                    }

                    error(response.message || "Dosya yüklenemedi.");
                };
                request.onerror = () => error("Sunucu bağlantısı kurulamadı.");
                request.send(payload);

                return {
                    abort: () => {
                        request.abort();
                        abort();
                    }
                };
            }
        }
    });

    const syncState = () => {
        const files = pond.getFiles();
        const readyFiles = files.filter((item) => item.status === FilePond.FileStatus.IDLE);
        const invalidFiles = files.filter((item) => item.status === FilePond.FileStatus.LOAD_ERROR);
        if (submit) {
            submit.disabled = uploadRunning || readyFiles.length === 0 || invalidFiles.length > 0;
        }
        if (!uploadRunning) {
            setStatus(files.length > 0 ? `${files.length} dosya hazır.` : "");
        }
    };

    pond.on("updatefiles", syncState);
    pond.on("addfile", syncState);
    pond.on("removefile", syncState);

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!form.reportValidity() || pond.getFiles().length === 0 || uploadRunning) {
            return;
        }

        uploadRunning = true;
        completedUploads = 0;
        syncState();
        setStatus("Dosyalar sunucuya aktarılıyor...");

        try {
            await pond.processFiles();
            const failed = pond.getFiles().some((item) => item.status === FilePond.FileStatus.PROCESSING_ERROR);
            if (failed) {
                setStatus(`${completedUploads} dosya yüklendi. Başarısız dosyaları kontrol edin.`);
                window.KirpiCore?.toast("Bazı dosyalar yüklenemedi.", "warning");
                return;
            }

            const message = `${completedUploads} dosya başarıyla yüklendi.`;
            window.KirpiCore?.persistPendingToast(message, "success");
            window.location.reload();
        } finally {
            uploadRunning = false;
            syncState();
        }
    });

    modalElement?.addEventListener("hidden.bs.modal", () => {
        if (!uploadRunning) {
            pond.removeFiles();
            form.reset();
            setStatus("");
        }
    });
})();
