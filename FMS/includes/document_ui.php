<?php
require_once __DIR__ . '/document_helpers.php';

function render_document_upload_fields(PDO $pdo): void
{
    $documentTypes = get_document_types($pdo);
    ?>
    <div class="document-upload-panel">
        <div class="document-upload-heading">
            <div class="document-upload-icon"><i class="fas fa-folder-open"></i></div>
            <div>
                <h4 class="mb-1">Application documents</h4>
                <p class="mb-0 text-muted">Upload clear copies of your documents. PDF, JPEG and PNG only, maximum 2 MB each.</p>
            </div>
        </div>
        <div class="row">
            <?php foreach ($documentTypes as $documentType): ?>
                <?php $typeId = (int) $documentType['id']; $required = (int) $documentType['is_required'] === 1; ?>
                <div class="col-lg-6 mb-3">
                    <label class="document-upload-tile" for="document_<?= $typeId ?>">
                        <span class="document-tile-icon"><i class="fas <?= $required ? 'fa-file-signature' : 'fa-file-upload' ?>"></i></span>
                        <span class="document-tile-content">
                            <strong><?= e($documentType['name']) ?> <?= $required ? '<span class="text-danger">*</span>' : '<small class="text-muted">(optional)</small>' ?></strong>
                            <small class="text-muted d-block"><?= e($documentType['description'] ?? 'Upload a supporting document') ?></small>
                            <span class="document-file-name" id="document_name_<?= $typeId ?>">Choose a file</span>
                        </span>
                        <i class="fas fa-cloud-upload-alt document-tile-action"></i>
                        <input type="file" id="document_<?= $typeId ?>" name="document_<?= $typeId ?>" accept="application/pdf,image/jpeg,image/png" class="document-file-input" data-name-target="document_name_<?= $typeId ?>">
                    </label>
                    <?php if ($documentType['name'] === 'Supportive Document'): ?>
                        <input type="text" name="document_label_<?= $typeId ?>" class="form-control form-control-sm mt-2" maxlength="150" placeholder="Optional description, e.g. CV or transcript">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="document-upload-note"><i class="fas fa-shield-alt mr-1"></i> Your documents are stored securely and can only be viewed by authorized users.</div>
    </div>
    <script>
    document.querySelectorAll('.document-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var target = document.getElementById(input.dataset.nameTarget);
            if (target) target.textContent = input.files.length ? input.files[0].name : 'Choose a file';
            if (target) target.classList.toggle('selected', input.files.length > 0);
        });
    });
    </script>
    <?php
}

function render_application_document_cards(array $documents, string $context = 'student'): void
{
    ?>
    <div class="document-review-panel">
        <div class="document-review-heading">
            <div>
                <h3 class="card-title mb-1"><i class="fas fa-paperclip mr-1"></i> Application documents</h3>
                <small class="text-muted">Review uploaded files before continuing.</small>
            </div>
            <span class="document-count-badge"><?= count($documents) ?> file<?= count($documents) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (empty($documents)): ?>
            <div class="document-empty-state"><i class="fas fa-inbox"></i><p class="mb-0">No documents uploaded yet.</p></div>
        <?php else: ?>
            <div class="document-card-grid">
                <?php foreach ($documents as $document): ?>
                    <?php $isImage = in_array($document['mime_type'], ['image/jpeg', 'image/png'], true); $documentId = (int) $document['id']; $previewUrl = '/FMS/actions/preview_document.php?id=' . $documentId; ?>
                    <article class="document-card">
                        <div class="document-card-preview <?= $isImage ? 'document-image-preview' : 'document-pdf-preview' ?>">
                            <?php if ($isImage): ?>
                                <img src="<?= e($previewUrl) ?>" alt="<?= e($document['document_type_name']) ?> preview">
                            <?php else: ?>
                                <i class="fas fa-file-pdf"></i><span>PDF</span>
                            <?php endif; ?>
                        </div>
                        <div class="document-card-body">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong><?= e($document['document_type_name']) ?></strong>
                                <?php if ($context === 'admin'): ?><span class="badge badge-<?= $document['verification_status'] === 'verified' ? 'success' : ($document['verification_status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e(ucfirst($document['verification_status'])) ?></span><?php endif; ?>
                            </div>
                            <div class="document-card-filename" title="<?= e($document['original_filename']) ?>"><?= e($document['original_filename']) ?></div>
                            <?php if (!empty($document['label'])): ?><small class="text-muted d-block mt-1"><?= e($document['label']) ?></small><?php endif; ?>
                            <small class="text-muted d-block mt-1"><?= number_format(((int) $document['file_size_bytes']) / 1048576, 2) ?> MB · <?= e(strtoupper(pathinfo($document['original_filename'], PATHINFO_EXTENSION))) ?></small>
                            <div class="document-card-actions mt-3">
                                <?php if ($isImage): ?>
                                    <button type="button" class="btn btn-sm btn-primary" data-fms-document-preview="<?= $documentId ?>"><i class="fas fa-search-plus mr-1"></i> Preview</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-primary" data-fms-document-preview="<?= $documentId ?>"><i class="fas fa-search-plus mr-1"></i> Preview</button>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-outline-secondary" href="/FMS/actions/download_document.php?id=<?= $documentId ?>"><i class="fas fa-download"></i></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="fms-document-viewer" id="fmsDocumentViewer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="fmsDocumentViewerTitle">
        <div class="fms-document-viewer-panel">
            <div class="fms-document-viewer-toolbar">
                <div><i class="fas fa-file-alt mr-2"></i><strong id="fmsDocumentViewerTitle">Document preview</strong></div>
                <button type="button" class="fms-document-viewer-close" data-fms-document-close aria-label="Close preview"><i class="fas fa-times"></i></button>
            </div>
            <div class="fms-document-viewer-body" id="fmsDocumentViewerBody"></div>
            <div class="fms-document-viewer-footer"><a id="fmsDocumentViewerNewTab" class="btn btn-outline-light" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i> Open in new tab</a><a id="fmsDocumentViewerDownload" class="btn btn-primary"><i class="fas fa-download mr-1"></i> Download</a></div>
        </div>
    </div>
    <script>
    (function () {
        var viewer = document.getElementById('fmsDocumentViewer');
        var body = document.getElementById('fmsDocumentViewerBody');
        var title = document.getElementById('fmsDocumentViewerTitle');
        var newTab = document.getElementById('fmsDocumentViewerNewTab');
        var download = document.getElementById('fmsDocumentViewerDownload');
        function closeViewer() { viewer.classList.remove('is-open'); viewer.setAttribute('aria-hidden', 'true'); document.body.classList.remove('fms-document-modal-open'); body.innerHTML = ''; }
        document.querySelectorAll('[data-fms-document-preview]').forEach(function (button) {
            button.addEventListener('click', function () {
                var card = button.closest('.document-card');
                var url = card.querySelector('.document-card-preview img')?.src || button.closest('.document-card').querySelector('.document-card-preview').dataset.previewUrl;
                var id = button.getAttribute('data-fms-document-preview');
                url = '/FMS/actions/preview_document.php?id=' + encodeURIComponent(id);
                var isPdf = card.querySelector('.document-pdf-preview') !== null;
                title.textContent = card.querySelector('.document-card-body strong').textContent;
                body.innerHTML = isPdf ? '<iframe class="fms-document-viewer-frame" src="' + url + '" title="Document PDF preview"></iframe>' : '<img class="fms-document-viewer-image" src="' + url + '" alt="Document preview">';
                newTab.href = url; download.href = '/FMS/actions/download_document.php?id=' + encodeURIComponent(id);
                viewer.classList.add('is-open'); viewer.setAttribute('aria-hidden', 'false'); document.body.classList.add('fms-document-modal-open');
            });
        });
        document.querySelectorAll('[data-fms-document-close]').forEach(function (button) { button.addEventListener('click', closeViewer); });
        viewer.addEventListener('click', function (event) { if (event.target === viewer) closeViewer(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && viewer.classList.contains('is-open')) closeViewer(); });
    }());
    </script>
    <?php
}
?>
<style>
.document-upload-panel,.document-review-panel{border:1px solid #dce6f1;border-radius:14px;background:linear-gradient(135deg,#fff 0%,#f6faff 100%);padding:1.25rem;box-shadow:0 6px 18px rgba(31,67,110,.06);margin-bottom:1.25rem}.document-upload-heading,.document-review-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}.document-upload-heading{justify-content:flex-start;gap:.85rem}.document-upload-icon{width:44px;height:44px;border-radius:12px;background:#e8f2ff;color:#2271c9;display:flex;align-items:center;justify-content:center;font-size:1.2rem}.document-upload-tile{display:flex;align-items:center;gap:.75rem;border:1px dashed #b7cbe2;border-radius:12px;background:#fff;padding:.85rem;cursor:pointer;transition:.2s;min-height:88px;margin:0}.document-upload-tile:hover{border-color:#2271c9;background:#f5faff;transform:translateY(-1px)}.document-tile-icon{width:38px;height:38px;border-radius:10px;background:#eef5fb;color:#2271c9;display:flex;align-items:center;justify-content:center}.document-tile-content{flex:1;min-width:0}.document-file-name{display:block;font-size:.78rem;color:#7c8b9b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.document-file-name.selected{color:#2271c9;font-weight:600}.document-tile-action{color:#8aa4be}.document-file-input{display:none}.document-upload-note{border-top:1px solid #e6eef6;padding-top:.8rem;font-size:.8rem;color:#6b7d90}.document-count-badge{background:#e8f2ff;color:#2271c9;border-radius:20px;padding:.3rem .65rem;font-size:.8rem;font-weight:600}.document-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1rem}.document-card{overflow:hidden;border:1px solid #e0e8f0;border-radius:12px;background:#fff;box-shadow:0 4px 12px rgba(31,67,110,.05)}.document-card-preview{height:135px;display:flex;align-items:center;justify-content:center;background:#edf4fb;color:#4b7197}.document-image-preview img{width:100%;height:100%;object-fit:cover}.document-pdf-preview{flex-direction:column;font-size:2.7rem;color:#d84b4b}.document-pdf-preview span{font-size:.75rem;font-weight:700;margin-top:.25rem}.document-card-body{padding:.85rem}.document-card-filename{font-size:.82rem;color:#5c6d7e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.document-card-actions{display:flex;gap:.4rem}.document-empty-state{text-align:center;padding:2rem;color:#8293a5}.document-empty-state i{font-size:2rem;margin-bottom:.5rem}.fms-document-viewer{position:fixed;inset:0;z-index:3000;display:flex;align-items:center;justify-content:center;background:rgba(5,18,35,.68);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease}.fms-document-viewer.is-open{opacity:1;visibility:visible}.fms-document-viewer-panel{width:96vw;height:94vh;display:flex;flex-direction:column;background:#101c2b;border:1px solid rgba(255,255,255,.18);border-radius:16px;box-shadow:0 24px 80px rgba(0,0,0,.45);overflow:hidden;transform:scale(.97);transition:transform .2s ease}.fms-document-viewer.is-open .fms-document-viewer-panel{transform:scale(1)}.fms-document-viewer-toolbar,.fms-document-viewer-footer{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;color:#fff;background:#17283d}.fms-document-viewer-close{border:0;background:transparent;color:#fff;font-size:1.35rem;cursor:pointer}.fms-document-viewer-body{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;padding:1rem;background:#26384c}.fms-document-viewer-image{max-width:100%;max-height:100%;object-fit:contain;box-shadow:0 8px 30px rgba(0,0,0,.3)}.fms-document-viewer-frame{width:100%;height:100%;border:0;background:#fff}.fms-document-viewer-footer{justify-content:flex-end;gap:.5rem}.fms-document-modal-open{overflow:hidden}
</style>
<?php
