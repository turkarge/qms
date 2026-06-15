<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/documents/language.php';

$tableReady = documents_tables_ready();
$documents = [];
$search = trim((string) ($_GET['search'] ?? ''));
$documentType = trim((string) ($_GET['document_type'] ?? ''));
$entityType = trim((string) ($_GET['entity_type'] ?? ''));
$entityId = (int) ($_GET['entity_id'] ?? 0);
$scope = in_array((string) ($_GET['scope'] ?? 'all'), ['all', 'recent', 'linked'], true)
    ? (string) ($_GET['scope'] ?? 'all')
    : 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 24);
$perPage = in_array($perPage, [12, 24, 48, 96], true) ? $perPage : 24;
$documentTypes = [];
$entityTypes = [];
$totalRecords = 0;
$stats = ['total_files' => 0, 'total_size' => 0, 'linked_files' => 0, 'recent_files' => 0];

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(d.original_name LIKE :search OR d.mime_type LIKE :search OR d.storage_path LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($documentType !== '') {
    $where[] = 'd.document_type = :document_type';
    $params[':document_type'] = $documentType;
}
if ($entityType !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM document_links dlf WHERE dlf.document_id = d.id AND dlf.entity_type = :entity_type)';
    $params[':entity_type'] = $entityType;
}
if ($entityId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM document_links dli WHERE dli.document_id = d.id AND dli.entity_id = :entity_id)';
    $params[':entity_id'] = $entityId;
}
if ($scope === 'recent') {
    $where[] = 'd.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
} elseif ($scope === 'linked') {
    $where[] = 'EXISTS (SELECT 1 FROM document_links dls WHERE dls.document_id = d.id)';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

if ($tableReady) {
    try {
        $documentTypes = db()->query("SELECT DISTINCT document_type FROM documents WHERE document_type <> '' ORDER BY document_type")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $entityTypes = db()->query("SELECT DISTINCT entity_type FROM document_links WHERE entity_type <> '' ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $statsRow = db()->query("
            SELECT COUNT(*) AS total_files,
                   COALESCE(SUM(file_size), 0) AS total_size,
                   SUM(EXISTS(SELECT 1 FROM document_links dl WHERE dl.document_id = documents.id)) AS linked_files,
                   SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS recent_files
            FROM documents
        ")->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach ($stats as $key => $value) {
            $stats[$key] = (int) ($statsRow[$key] ?? 0);
        }

        $countStmt = db()->prepare("SELECT COUNT(*) FROM documents d {$whereSql}");
        $countStmt->execute($params);
        $totalRecords = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRecords / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = db()->prepare("
            SELECT d.id, d.document_type, d.original_name, d.mime_type, d.file_size, d.created_at,
                   u.name AS uploaded_by_name,
                   COUNT(dl.id) AS link_count,
                   GROUP_CONCAT(DISTINCT CONCAT(dl.entity_type, '#', dl.entity_id) ORDER BY dl.entity_type, dl.entity_id SEPARATOR ', ') AS entity_links
            FROM documents d
            LEFT JOIN users u ON u.id = d.uploaded_by_user_id
            LEFT JOIN document_links dl ON dl.document_id = d.id
            {$whereSql}
            GROUP BY d.id, d.document_type, d.original_name, d.mime_type, d.file_size, d.created_at, u.name
            ORDER BY d.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('documents view list error: ' . $e->getMessage());
        $documents = [];
    }
}

$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$fromRecord = $totalRecords > 0 ? (($page - 1) * $perPage) + 1 : 0;
$toRecord = min($totalRecords, $page * $perPage);
$filterParams = array_filter([
    'search' => $search,
    'document_type' => $documentType,
    'entity_type' => $entityType,
    'entity_id' => $entityId > 0 ? $entityId : null,
    'scope' => $scope !== 'all' ? $scope : null,
    'per_page' => $perPage,
], static fn ($value): bool => $value !== '' && $value !== null);
$pageUrl = static function (int $targetPage) use ($filterParams): string {
    return base_url('documents/view?' . http_build_query($filterParams + ['page' => $targetPage]));
};
$csvExportUrl = base_url('documents/actions/export?' . http_build_query($filterParams + ['format' => 'csv']));
$xlsExportUrl = base_url('documents/actions/export?' . http_build_query($filterParams + ['format' => 'xls']));
$showingText = str_replace(
    ['{total}', '{from}', '{to}'],
    [(string) $totalRecords, (string) $fromRecord, (string) $toRecord],
    documents_lang('showing_records')
);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(documents_lang('documents')); ?></div>
                <h2 class="page-title"><?php echo e(documents_lang('file_library')); ?></h2>
                <div class="text-secondary mt-1"><?php echo e(documents_lang('page_hint')); ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-download"></i> Export
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="<?php echo e($csvExportUrl); ?>" class="dropdown-item"><i class="ti ti-file-type-csv me-2"></i><?php echo e(documents_lang('export_csv')); ?></a>
                            <a href="<?php echo e($xlsExportUrl); ?>" class="dropdown-item"><i class="ti ti-file-spreadsheet me-2"></i><?php echo e(documents_lang('export_excel')); ?></a>
                        </div>
                    </div>
                    <?php if (check_permission('documents.upload')): ?>
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#document-upload-modal">
                            <i class="ti ti-upload"></i> <?php echo e(documents_lang('upload_files')); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($tableReady && check_permission('documents.upload')): ?>
<div class="modal modal-blur fade" id="document-upload-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="<?php echo base_url('documents/actions/upload'); ?>" method="post" enctype="multipart/form-data" class="modal-content" data-document-filepond-form>
            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title"><?php echo e(documents_lang('upload_files')); ?></h3>
                    <div class="text-secondary small mt-1"><?php echo e(documents_lang('upload_modal_hint')); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(documents_lang('close')); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <input
                            type="file"
                            name="document_file"
                            multiple
                            data-document-filepond
                            data-max-file-size="<?php echo documents_max_upload_size(); ?>"
                            data-accepted-file-types="<?php echo e(implode(',', array_keys(documents_allowed_mime_types()))); ?>"
                        >
                        <div class="text-secondary small mt-2">
                            <?php echo e(implode(', ', array_map('strtoupper', array_unique(array_values(documents_allowed_mime_types()))))); ?>
                            &middot; <?php echo e(documents_format_size(documents_max_upload_size())); ?> / <?php echo e(documents_lang('file')); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="document-upload-settings">
                            <div>
                                <label class="form-label form-required"><?php echo e(documents_lang('document_type')); ?></label>
                                <input type="text" name="document_type" class="form-control" required value="attachment">
                                <div class="form-hint"><?php echo e(documents_lang('type_hint')); ?></div>
                            </div>
                            <div>
                                <label class="form-label"><?php echo e(documents_lang('entity_type')); ?></label>
                                <input type="text" name="entity_type" class="form-control">
                            </div>
                            <div>
                                <label class="form-label"><?php echo e(documents_lang('entity_id')); ?></label>
                                <input type="number" name="entity_id" class="form-control" min="1">
                                <div class="form-hint"><?php echo e(documents_lang('entity_hint')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto text-secondary small" data-document-upload-status></div>
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal"><?php echo e(documents_lang('cancel')); ?></button>
                <button type="submit" class="btn btn-primary" disabled data-document-upload-submit>
                    <i class="ti ti-upload"></i> <?php echo e(documents_lang('upload_all')); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="page-body">
    <div class="container-xl" data-document-manager>
        <?php if (!$tableReady): ?>
            <div class="alert alert-warning"><?php echo e(documents_lang('tables_missing')); ?></div>
        <?php else: ?>
            <div class="document-explorer">
                <aside class="document-explorer__sidebar">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><?php echo e(documents_lang('collections')); ?></h3></div>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo base_url('documents/view'); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $scope === 'all' && $documentType === '' ? 'active' : ''; ?>">
                                <i class="ti ti-files"></i><span class="flex-fill"><?php echo e(documents_lang('all_files')); ?></span><span class="badge bg-secondary-lt"><?php echo $stats['total_files']; ?></span>
                            </a>
                            <a href="<?php echo base_url('documents/view?scope=recent'); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $scope === 'recent' ? 'active' : ''; ?>">
                                <i class="ti ti-clock"></i><span class="flex-fill"><?php echo e(documents_lang('recent_files')); ?></span><span class="badge bg-orange-lt"><?php echo $stats['recent_files']; ?></span>
                            </a>
                            <a href="<?php echo base_url('documents/view?scope=linked'); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?php echo $scope === 'linked' ? 'active' : ''; ?>">
                                <i class="ti ti-link"></i><span class="flex-fill"><?php echo e(documents_lang('linked_files')); ?></span><span class="badge bg-green-lt"><?php echo $stats['linked_files']; ?></span>
                            </a>
                        </div>
                        <?php if ($documentTypes !== []): ?>
                            <div class="card-body border-top py-3">
                                <div class="text-secondary text-uppercase small fw-bold mb-2"><?php echo e(documents_lang('document_types')); ?></div>
                                <div class="nav nav-pills flex-column">
                                    <?php foreach ($documentTypes as $type): ?>
                                        <a href="<?php echo e(base_url('documents/view?document_type=' . rawurlencode((string) $type))); ?>" class="nav-link d-flex align-items-center gap-2 <?php echo $documentType === (string) $type ? 'active' : ''; ?>">
                                            <i class="ti ti-folder"></i><span class="text-truncate"><?php echo e((string) $type); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>

                <section class="document-explorer__workspace">
            <div class="document-toolbar mb-3">
                <form method="get" action="<?php echo base_url('documents/view'); ?>" class="document-filter-form document-filter-form--compact">
                    <div class="input-icon document-search">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="search" name="search" class="form-control" value="<?php echo e($search); ?>" placeholder="<?php echo e(documents_lang('search_placeholder')); ?>">
                    </div>
                    <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                    <?php if ($scope !== 'all'): ?><input type="hidden" name="scope" value="<?php echo e($scope); ?>"><?php endif; ?>
                    <?php if ($documentType !== ''): ?><input type="hidden" name="document_type" value="<?php echo e($documentType); ?>"><?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-icon" title="<?php echo e(documents_lang('search')); ?>"><i class="ti ti-search"></i></button>
                    <button class="btn btn-icon btn-outline-secondary <?php echo ($entityType !== '' || $entityId > 0) ? 'active' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#document-advanced-filters" title="<?php echo e(documents_lang('filters')); ?>"><i class="ti ti-adjustments-horizontal"></i></button>
                    <a href="<?php echo base_url('documents/view'); ?>" class="btn btn-icon btn-outline-secondary" title="<?php echo e(documents_lang('clear')); ?>"><i class="ti ti-x"></i></a>
                </form>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-icon btn-outline-secondary active" data-document-view="grid" title="<?php echo e(documents_lang('grid_view')); ?>"><i class="ti ti-layout-grid"></i></button>
                    <button type="button" class="btn btn-icon btn-outline-secondary" data-document-view="list" title="<?php echo e(documents_lang('list_view')); ?>"><i class="ti ti-list"></i></button>
                </div>
            </div>

            <div class="collapse <?php echo ($entityType !== '' || $entityId > 0) ? 'show' : ''; ?> mb-3" id="document-advanced-filters">
                <form method="get" action="<?php echo base_url('documents/view'); ?>" class="card card-body document-advanced-filters">
                    <input type="hidden" name="search" value="<?php echo e($search); ?>">
                    <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                    <?php if ($scope !== 'all'): ?><input type="hidden" name="scope" value="<?php echo e($scope); ?>"><?php endif; ?>
                    <select name="document_type" class="form-select">
                        <option value=""><?php echo e(documents_lang('all_document_types')); ?></option>
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?php echo e((string) $type); ?>" <?php echo $documentType === (string) $type ? 'selected' : ''; ?>><?php echo e((string) $type); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="entity_type" class="form-select">
                        <option value=""><?php echo e(documents_lang('all_entity_types')); ?></option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?php echo e((string) $type); ?>" <?php echo $entityType === (string) $type ? 'selected' : ''; ?>><?php echo e((string) $type); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="entity_id" class="form-control" min="1" value="<?php echo $entityId > 0 ? $entityId : ''; ?>" placeholder="Entity ID">
                    <button type="submit" class="btn btn-outline-primary"><i class="ti ti-filter"></i><?php echo e(documents_lang('filter')); ?></button>
                </form>
            </div>

            <div class="document-selection-bar mb-3" data-document-selection-bar hidden>
                <div class="d-flex align-items-center gap-3">
                    <strong data-document-selection-count></strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-document-download-selected><i class="ti ti-download"></i><?php echo e(documents_lang('download_selected')); ?></button>
                    <?php if (check_permission('documents.manage')): ?>
                        <form id="documents-bulk-delete-form" action="<?php echo base_url('documents/actions/bulk-delete'); ?>" method="post" data-ajax="true">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <input type="hidden" name="ids" value="[]" data-document-selected-ids>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-confirm="<?php echo e(documents_lang('delete_selected')); ?>?" data-form="documents-bulk-delete-form"><i class="ti ti-trash"></i><?php echo e(documents_lang('delete_selected')); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-ghost-secondary" data-document-clear-selection><?php echo e(documents_lang('clear')); ?></button>
            </div>

            <?php if (empty($documents)): ?>
                <div class="document-empty-state">
                    <i class="ti ti-folder-open"></i>
                    <h3><?php echo e(documents_lang('no_records')); ?></h3>
                    <p><?php echo e(documents_lang('drop_files_hint')); ?></p>
                </div>
            <?php else: ?>
                <div class="document-grid" data-document-collection data-view="grid">
                    <?php foreach ($documents as $document): ?>
                        <?php
                        $documentId = (int) ($document['id'] ?? 0);
                        $presentation = documents_file_presentation((string) ($document['mime_type'] ?? ''), (string) ($document['original_name'] ?? ''));
                        $downloadUrl = base_url('documents/actions/download/' . $documentId);
                        $deleteFormId = 'document-delete-' . $documentId;
                        ?>
                        <article
                            class="document-item"
                            tabindex="0"
                            data-document-item
                            data-document-id="<?php echo $documentId; ?>"
                            data-download-url="<?php echo e($downloadUrl); ?>"
                            data-document-name="<?php echo e((string) ($document['original_name'] ?? '')); ?>"
                            data-document-type="<?php echo e((string) ($document['document_type'] ?? '')); ?>"
                            data-document-mime="<?php echo e((string) ($document['mime_type'] ?? '')); ?>"
                            data-document-size="<?php echo e(documents_format_size((int) ($document['file_size'] ?? 0))); ?>"
                            data-document-date="<?php echo e(kirpi_format_datetime((string) ($document['created_at'] ?? ''))); ?>"
                            data-document-owner="<?php echo e((string) ($document['uploaded_by_name'] ?? '-')); ?>"
                            data-document-links="<?php echo e((string) ($document['entity_links'] ?? '-')); ?>"
                            data-document-icon="<?php echo e($presentation['icon']); ?>"
                            data-document-tone="<?php echo e($presentation['tone']); ?>"
                        >
                            <label class="document-select"><input type="checkbox" class="form-check-input" data-document-select value="<?php echo $documentId; ?>"><span class="visually-hidden"><?php echo e(documents_lang('select_files')); ?></span></label>
                            <div class="document-item__visual bg-<?php echo e($presentation['tone']); ?>-lt"><i class="ti <?php echo e($presentation['icon']); ?>"></i><span><?php echo e($presentation['label']); ?></span></div>
                            <div class="document-item__body">
                                <div class="document-item__name" title="<?php echo e((string) ($document['original_name'] ?? '')); ?>"><?php echo e((string) ($document['original_name'] ?? '')); ?></div>
                                <div class="document-item__meta"><span><?php echo e(documents_format_size((int) ($document['file_size'] ?? 0))); ?></span><span><?php echo e(kirpi_format_datetime((string) ($document['created_at'] ?? ''))); ?></span></div>
                                <div class="document-item__details">
                                    <span class="badge bg-secondary-lt"><?php echo e((string) ($document['document_type'] ?? '')); ?></span>
                                    <?php if ((int) ($document['link_count'] ?? 0) > 0): ?><span class="badge bg-blue-lt"><i class="ti ti-link"></i><?php echo (int) $document['link_count']; ?></span><?php endif; ?>
                                    <span class="text-secondary text-truncate"><?php echo e((string) ($document['uploaded_by_name'] ?? '-')); ?></span>
                                </div>
                                <?php if (!empty($document['entity_links'])): ?><div class="document-item__links text-secondary"><?php echo e((string) $document['entity_links']); ?></div><?php endif; ?>
                            </div>
                            <div class="document-item__actions">
                                <a href="<?php echo e($downloadUrl); ?>" class="btn btn-icon btn-sm btn-outline-primary" title="<?php echo e(documents_lang('download')); ?>"><i class="ti ti-download"></i></a>
                                <?php if (check_permission('documents.manage')): ?>
                                    <form id="<?php echo e($deleteFormId); ?>" action="<?php echo base_url('documents/actions/delete'); ?>" method="post" data-ajax="true">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>"><input type="hidden" name="id" value="<?php echo $documentId; ?>">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-confirm="<?php echo e(documents_lang('delete')); ?>?" data-form="<?php echo e($deleteFormId); ?>" title="<?php echo e(documents_lang('delete')); ?>"><i class="ti ti-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-4">
                <div class="text-secondary"><?php echo e($showingText); ?></div>
                <div class="d-flex align-items-center gap-3">
                    <form method="get" action="<?php echo base_url('documents/view'); ?>" class="d-flex align-items-center gap-2">
                        <?php foreach ($filterParams as $key => $value): if ($key === 'per_page') continue; ?><input type="hidden" name="<?php echo e((string) $key); ?>" value="<?php echo e((string) $value); ?>"><?php endforeach; ?>
                        <label class="text-secondary text-nowrap"><?php echo e(documents_lang('per_page')); ?></label>
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ([12, 24, 48, 96] as $size): ?><option value="<?php echo $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?>
                        </select>
                    </form>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo e($pageUrl(max(1, $page - 1))); ?>"><i class="ti ti-chevron-left"></i><?php echo e(documents_lang('previous')); ?></a>
                        <span class="btn btn-sm btn-outline-secondary disabled"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
                        <a class="btn btn-sm btn-outline-secondary <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo e($pageUrl(min($totalPages, $page + 1))); ?>"><?php echo e(documents_lang('next')); ?><i class="ti ti-chevron-right"></i></a>
                    </div>
                </div>
            </div>
                </section>

            </div>

            <div class="offcanvas offcanvas-end" tabindex="-1" id="document-details" data-document-inspector aria-labelledby="document-details-title">
                <div class="offcanvas-header border-bottom">
                    <h3 class="offcanvas-title" id="document-details-title"><?php echo e(documents_lang('file_details')); ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo e(documents_lang('close')); ?>"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div data-document-inspector-content>
                            <div class="card-body text-center border-bottom">
                                <span class="avatar avatar-xl mb-3" data-document-inspector-avatar><i class="ti ti-file fs-1"></i></span>
                                <div class="fw-semibold text-break" data-document-inspector-name></div>
                                <div class="text-secondary small mt-1" data-document-inspector-mime></div>
                            </div>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item"><div class="text-secondary small"><?php echo e(documents_lang('document_type')); ?></div><div data-document-inspector-type></div></div>
                                <div class="list-group-item"><div class="text-secondary small"><?php echo e(documents_lang('file_size')); ?></div><div data-document-inspector-size></div></div>
                                <div class="list-group-item"><div class="text-secondary small"><?php echo e(documents_lang('uploaded_by')); ?></div><div data-document-inspector-owner></div></div>
                                <div class="list-group-item"><div class="text-secondary small"><?php echo e(documents_lang('created_at')); ?></div><div data-document-inspector-date></div></div>
                                <div class="list-group-item"><div class="text-secondary small"><?php echo e(documents_lang('entity_links')); ?></div><div class="text-break" data-document-inspector-links></div></div>
                            </div>
                            <div class="card-footer">
                                <a href="#" class="btn btn-primary w-100" data-document-inspector-download><i class="ti ti-download"></i><?php echo e(documents_lang('download')); ?></a>
                            </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
