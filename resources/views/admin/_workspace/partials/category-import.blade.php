<dialog id="admin-csv-import" class="mgmt-dialog" aria-labelledby="admin-csv-import-title">
    <form action="{{ route('admin.csv.categories.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mgmt-dialog-head">
            <div><h2 id="admin-csv-import-title">Import categories</h2><p>CSV import · up to 200 categories</p></div>
            <button type="button" class="sp-icon-button" data-admin-csv-import-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mgmt-dialog-body">
            <p class="mgmt-hint">Required column: <strong>name</strong>. Optional: <strong>is_active</strong> (1 or 0), <strong>img</strong>. Existing categories cannot be overwritten.</p>
            <div class="mgmt-field"><label for="admin-csv-file">CSV file</label><input id="admin-csv-file" class="sp-input" type="file" name="file" accept=".csv,.txt,text/csv" required></div>
        </div>
        <div class="mgmt-dialog-footer">
            <button type="button" class="sp-btn sp-btn-secondary" data-admin-csv-import-close>Cancel</button>
            <button type="submit" class="sp-btn sp-btn-primary"><i class="bi bi-upload"></i>Import CSV</button>
        </div>
    </form>
</dialog>
