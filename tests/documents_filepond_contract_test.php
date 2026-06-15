<?php

$root = dirname(__DIR__);
$view = file_get_contents($root . '/modules/documents/pages/view.php');
$script = file_get_contents($root . '/modules/documents/scripts/view.js');
$upload = file_get_contents($root . '/modules/documents/actions/upload.php');
$header = file_get_contents($root . '/layouts/header.php');
$footer = file_get_contents($root . '/layouts/footer.php');
$manifest = file_get_contents($root . '/modules/documents/module.json');

$assertions = [
    'FilePond input is present' => str_contains($view, 'data-document-filepond'),
    'upload opens as a modal' => str_contains($view, 'data-bs-target="#document-upload-modal"'),
    'FilePond assets are route-scoped' => str_contains($header, '$isDocumentsPage')
        && str_contains($footer, "modules/documents/pages/view.php"),
    'FilePond core and validation plugins are loaded' => str_contains($footer, 'filepond.min.js')
        && str_contains($footer, 'filepond-plugin-file-validate-type.min.js')
        && str_contains($footer, 'filepond-plugin-file-validate-size.min.js'),
    'client sends CSRF and metadata' => str_contains($script, 'payload.append("csrf_token"')
        && str_contains($script, 'payload.append("document_type"')
        && str_contains($script, 'payload.append("entity_type"'),
    'server keeps the standard storage path' => str_contains($upload, 'document_store_upload($file, $documentType)'),
    'FilePond uploads remain audited' => str_contains($upload, "kirpi_audit_log('document_upload'"),
    'FilePond requests avoid notification storms' => str_contains($upload, 'if (!$isFilePondUpload)'),
    'explorer has collection workspace and on-demand inspector regions' => str_contains($view, 'document-explorer__sidebar')
        && str_contains($view, 'document-explorer__workspace')
        && str_contains($view, 'offcanvas offcanvas-end')
        && str_contains($view, 'data-document-inspector'),
    'dashboard stat cards are removed from the working surface' => !str_contains($view, 'document-stat-card'),
    'advanced filters stay collapsed until requested' => str_contains($view, 'document-advanced-filters')
        && str_contains($view, 'data-bs-target="#document-advanced-filters"'),
    'document cards expose inspector metadata' => str_contains($view, 'data-document-name=')
        && str_contains($view, 'data-document-mime=')
        && str_contains($view, 'data-document-owner='),
    'inspector interaction is keyboard accessible' => str_contains($view, 'tabindex="0"')
        && str_contains($script, 'event.key === "Enter"'),
    'Documents manifest exposes the new file manager version' => str_contains($manifest, '"version": "2.1.0"'),
];

$failed = [];
foreach ($assertions as $label => $passed) {
    if (!$passed) {
        $failed[] = $label;
    }
}

if ($failed !== []) {
    fwrite(STDERR, "Documents FilePond contract failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Documents FilePond contract passed (" . count($assertions) . " assertions).\n";
