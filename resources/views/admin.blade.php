<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Media Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
        }

        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header img {
            height: 46px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-container {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .stat-badge {
            padding: 0.5rem 1rem;
            background: #ebf8ff;
            color: #2c5282;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .container {
            display: flex;
            height: calc(100vh - 73px);
        }

        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f7fafc;
        }

        .sidebar-header h2 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #718096;
        }

        .filter-section {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-section h3 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #718096;
            margin-bottom: 0.75rem;
        }

        .filter-option {
            padding: 0.625rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .filter-option:hover {
            background: #f7fafc;
        }

        .filter-option.active {
            background: #ebf8ff;
            color: #2c5282;
            font-weight: 500;
        }

        .filter-count {
            background: #edf2f7;
            color: #4a5568;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .filter-option.active .filter-count {
            background: #bee3f8;
            color: #2c5282;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        .toolbar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .toolbar-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3182ce;
            color: white;
        }

        .btn-primary:hover {
            background: #2c5282;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .media-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.2s;
            position: relative;
        }

        .media-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .media-card.selected {
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .media-checkbox {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
            z-index: 10;
        }

        .media-preview {
            width: 100%;
            height: 200px;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-preview-icon {
            font-size: 3rem;
            color: #cbd5e0;
        }

        .media-info {
            padding: 1rem;
        }

        .media-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .media-meta {
            font-size: 0.75rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }

        .media-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: #edf2f7;
            color: #4a5568;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .media-actions {
            display: flex;
            gap: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f7fafc;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f7fafc;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 0;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .tag-input-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            min-height: 48px;
        }

        .tag-input-container:focus-within {
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .tag-item {
            background: #ebf8ff;
            color: #2c5282;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tag-remove {
            cursor: pointer;
            font-weight: bold;
            color: #2c5282;
        }

        .tag-remove:hover {
            color: #1a365d;
        }

        .tag-input {
            flex: 1;
            min-width: 120px;
            border: none;
            outline: none;
            padding: 0.25rem;
            font-size: 0.875rem;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .spinner {
            border: 3px solid #e2e8f0;
            border-top: 3px solid #3182ce;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            opacity: 0.5;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .page-btn:hover:not(:disabled) {
            background: #f7fafc;
        }

        .page-btn.active {
            background: #3182ce;
            color: white;
            border-color: #3182ce;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .bulk-actions-bar {
            display: none;
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            align-items: center;
            gap: 1rem;
            z-index: 100;
        }

        .bulk-actions-bar.active {
            display: flex;
        }

        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-badge.image {
            background: #fed7d7;
            color: #742a2a;
        }

        .type-badge.video {
            background: #e6fffa;
            color: #234e52;
        }

        .type-badge.audio {
            background: #fef5e7;
            color: #7c2d12;
        }

        .type-badge.document {
            background: #e9d8fd;
            color: #44337a;
        }

        .file-size {
            color: #718096;
            font-size: 0.75rem;
        }

        .modal-error-container {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            display: none;
        }

        .btn-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
        }

        .slow-upload-hint {
            font-size: 0.8rem;
            color: #718096;
            text-align: center;
            margin-top: 0.5rem;
            display: none;
        }

        .toast-notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-0.5rem);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
        }

        .toast-notification.active {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .drag-handle {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 26px;
            height: 26px;
            background: rgba(0, 0, 0, 0.45);
            color: white;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            opacity: 0;
            transition: opacity 0.15s;
            z-index: 11;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .media-card:hover .drag-handle {
            opacity: 1;
        }

        .media-card.dragging {
            opacity: 0.4;
            border: 2px dashed #3182ce;
        }

        .media-card.drag-over {
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.3);
        }

        .order-saving-indicator {
            display: none;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #718096;
        }

        .order-saving-indicator .spinner {
            width: 14px;
            height: 14px;
            border-width: 2px;
            margin: 0;
        }

        /* ── View toggle ───────────────────────────────────────────────────── */
        .view-toggle {
            display: flex;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .view-toggle-btn {
            padding: 0.5rem 0.625rem;
            background: white;
            border: none;
            cursor: pointer;
            color: #718096;
            transition: background 0.15s, color 0.15s;
            display: flex;
            align-items: center;
            line-height: 0;
        }

        .view-toggle-btn + .view-toggle-btn {
            border-left: 1px solid #e2e8f0;
        }

        .view-toggle-btn:hover {
            background: #f7fafc;
            color: #2d3748;
        }

        .view-toggle-btn.active {
            background: #3182ce;
            color: white;
        }

        /* ── List view ─────────────────────────────────────────────────────── */
        .media-list {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .media-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1rem;
            border-bottom: 1px solid #f0f4f8;
            transition: background 0.15s;
            position: relative;
            border-left: 2px solid transparent;
            border-right: 2px solid transparent;
        }

        .media-row:last-child {
            border-bottom: none;
        }

        .media-row:hover {
            background: #f7fafc;
        }

        .media-row.selected {
            background: #ebf8ff;
        }

        .media-row.dragging {
            opacity: 0.4;
            background: #ebf8ff;
        }

        .media-row.drag-over-before {
            border-top: 2px solid #3182ce;
        }

        .media-row.drag-over-after {
            border-bottom: 2px solid #3182ce;
        }

        /* Drag handle + checkbox overrides for list rows (inline flex items) */
        .media-row .drag-handle {
            position: static;
            width: 22px;
            height: 22px;
            background: transparent;
            color: #a0aec0;
            opacity: 1;
            flex-shrink: 0;
        }

        .media-row:hover .drag-handle {
            color: #4a5568;
        }

        .media-row .media-checkbox {
            position: static;
            width: 16px;
            height: 16px;
        }

        .row-thumb {
            width: 48px;
            height: 48px;
            border-radius: 0.25rem;
            overflow: hidden;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .row-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .row-thumb-icon {
            font-size: 1.4rem;
            line-height: 1;
        }

        .row-checkbox {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }

        .row-info {
            flex: 1;
            min-width: 0;
        }

        .row-name {
            font-weight: 500;
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .row-desc {
            font-size: 0.75rem;
            color: #718096;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-top: 0.1rem;
        }

        .row-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            min-width: 120px;
        }

        .row-tags {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
            max-width: 200px;
            overflow: hidden;
        }

        .row-actions {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
        }

        .row-actions .action-btn {
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <img src="https://emilecaron.be/assets/LOGO_CARONE_main.png" alt="Carone Logo">
            <a href="https://github.com/EmileCar/laravel-media" target="_blank" style="text-decoration: none; color: #007bed; font-weight: bold; transition: text-decoration 0.2s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                Media Manager
            </a>
        </h1>

        <div class="header-right">
            <div class="stats-container" id="statsContainer">
                <div class="stat-badge" id="totalMediaStat">Loading...</div>
            </div>
            <a href="{{ config('app.url') }}" target="_blank" style="text-decoration: none; color: #3182ce; font-weight: 500; font-style: italic; transition: text-decoration 0.2s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                Back to Site
            </a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Filters</h2>
            </div>

            <div class="filter-section">
                <h3>Type</h3>
                <div class="filter-option active" data-filter-type="type" data-value="all" onclick="applyFilter('type', 'all')">
                    <span>All Media</span>
                    <span class="filter-count" id="count-all">0</span>
                </div>
                @foreach($enabledTypes as $type)
                <div class="filter-option" data-filter-type="type" data-value="{{ $type }}" onclick="applyFilter('type', '{{ $type }}')">
                    <span style="text-transform: capitalize;">{{ ucfirst($type) }}</span>
                    <span class="filter-count" id="count-{{ $type }}">0</span>
                </div>
                @endforeach
            </div>

            <div class="filter-section">
                <h3>Source</h3>
                <div class="filter-option active" data-filter-type="source" data-value="all" onclick="applyFilter('source', 'all')">
                    <span>All Sources</span>
                    <span class="filter-count" id="count-source-all">-</span>
                </div>
                <div class="filter-option" data-filter-type="source" data-value="local" onclick="applyFilter('source', 'local')">
                    <span>Local</span>
                    <span class="filter-count" id="count-source-local">0</span>
                </div>
                <div class="filter-option" data-filter-type="source" data-value="external" onclick="applyFilter('source', 'external')">
                    <span>External</span>
                    <span class="filter-count" id="count-source-external">0</span>
                </div>
            </div>

            @if($tagsEnabled)
            <div class="filter-section" id="tagsFilter">
                <h3>Tags</h3>
                <div class="filter-option active" data-filter-type="tag" data-value="" onclick="applyFilter('tag', '')">
                    <span>All Tags</span>
                    <span class="filter-count" id="count-tag-all">-</span>
                </div>
                <div id="tagsList"></div>
            </div>
            @endif
        </div>

        <div class="main-content">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search media by name or description..." oninput="debounceSearch()">
                </div>
                <div id="orderSavingIndicator" class="order-saving-indicator">
                    <div class="spinner"></div>
                    <span>Saving order...</span>
                </div>
                <div class="view-toggle">
                    <button class="view-toggle-btn active" id="viewToggleGrid" onclick="setView('grid')" title="Grid view">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/>
                            <rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/>
                        </svg>
                    </button>
                    <button class="view-toggle-btn" id="viewToggleList" onclick="setView('list')" title="List view">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <rect x="1" y="2" width="14" height="2.5" rx="1"/><rect x="1" y="6.75" width="14" height="2.5" rx="1"/>
                            <rect x="1" y="11.5" width="14" height="2.5" rx="1"/>
                        </svg>
                    </button>
                </div>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="openUploadModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Upload Media
                    </button>
                    <button class="btn btn-secondary" onclick="refreshMedia(this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div id="mediaContainer">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading media...</p>
                </div>
            </div>

            <div id="paginationContainer"></div>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar" id="bulkActionsBar">
        <span id="selectedCount">0 selected</span>
        <button class="btn btn-danger" onclick="bulkDelete(this)">
            Delete Selected
        </button>
        <button class="btn btn-secondary" onclick="clearSelection()">
            Clear Selection
        </button>
    </div>

    <div id="toastNotification" class="toast-notification"></div>

    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Media</h3>
            </div>
            <form id="uploadForm" onsubmit="uploadMedia(event)">
                <div class="modal-body">
                    <div id="uploadModalError" class="modal-error-container"></div>
                    <div class="form-group">
                        <label>Source *</label>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="uploadSource" value="local" onchange="switchUploadSource('local')" style="margin-right: 0.5rem;">
                                Local File
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="uploadSource" value="external" onchange="switchUploadSource('external')" style="margin-right: 0.5rem;">
                                External URL
                            </label>
                        </div>
                    </div>

                    <!-- Form Fields Container - Hidden until source is selected -->
                    <div id="uploadFormFields" style="display: none;">
                    <div class="form-group">
                        <label>Media Type *</label>
                        <select class="form-control" id="uploadType" required onchange="handleTypeChange()">
                            <option value="">Select type</option>
                            @foreach($enabledTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="uploadFileGroup">
                        <label>File *</label>
                        <input type="file" class="form-control" id="uploadFile">
                    </div>
                    <div class="form-group" id="uploadUrlGroup" style="display: none;">
                        <label>URL *</label>
                        <input type="url" class="form-control" id="uploadUrl" placeholder="https://example.com/media.jpg">
                    </div>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" class="form-control" id="uploadName" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="uploadDescription"></textarea>
                    </div>
                    @if($tagsEnabled)
                    <div class="form-group">
                        <label>Tags</label>
                        <div class="tag-input-container" id="uploadTagsContainer" onclick="document.getElementById('uploadTagInput').focus()">
                            <input type="text" class="tag-input" id="uploadTagInput" placeholder="Add tags..." onkeydown="handleTagInput(event)">
                        </div>
                    </div>
                    @endif

                    <!-- Auto-generate thumbnail for local images only -->
                    <div class="form-group" id="autoThumbnailGroup" style="display: none;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="uploadAutoThumbnail" checked onchange="toggleAutoThumbnail()" style="margin-right: 0.5rem;">
                            Auto-generate thumbnail
                        </label>
                    </div>

                    <!-- Advanced Thumbnail Options - Always visible but collapsed -->
                    <div class="form-group">
                        <label style="cursor: pointer; display: flex; align-items: center; color: #3182ce;" onclick="toggleAdvancedThumbnail()">
                            <span id="advancedThumbnailIcon" style="margin-right: 0.5rem;">▶</span>
                            Advanced Thumbnail Options
                        </label>
                        <div id="advancedThumbnailContent" style="display: none; margin-top: 0.75rem; padding: 0.75rem; background: #f7fafc; border-radius: 0.375rem;">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label>Upload Custom Thumbnail</label>
                                <input type="file" class="form-control" id="uploadThumbnailFile" accept="image/*">
                            </div>
                            <div style="text-align: center; color: #718096; margin: 0.5rem 0;">OR</div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Thumbnail URL</label>
                                <input type="url" class="form-control" id="uploadThumbnailUrl" placeholder="https://example.com/thumbnail.jpg">
                            </div>
                        </div>
                    </div>
                    </div> <!-- End of uploadFormFields container -->
                </div>
                <div class="modal-footer" style="flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="uploadSubmitBtn" disabled>
                            <span id="uploadBtnContent">Upload</span>
                        </button>
                    </div>
                    <div id="slowUploadHint" class="slow-upload-hint">Loading large files may cause the request to be slower</div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Media</h3>
            </div>
            <form id="editForm" onsubmit="saveEdit(event)">
                <input type="hidden" id="editMediaId">
                <div class="modal-body">
                    <div id="editModalError" class="modal-error-container"></div>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" class="form-control" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="editDescription"></textarea>
                    </div>
                    @if($tagsEnabled)
                    <div class="form-group">
                        <label>Tags</label>
                        <div class="tag-input-container" id="editTagsContainer" onclick="document.getElementById('editTagInput').focus()">
                            <input type="text" class="tag-input" id="editTagInput" placeholder="Add tags..." onkeydown="handleEditTagInput(event)">
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <span id="editBtnContent">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const apiBaseUrl = '/api/media';
        const adminApiUrl = '/api/{{ config("media.admin.route_prefix", "admin/media") }}';

        let currentFilters = {
            type: 'all',
            source: 'all',
            tag: '',
            search: '',
            page: 1,
            per_page: 24
        };

        let selectedMedia = new Set();
        let uploadTags = [];
        let editTags = [];
        let allTags = [];
        let searchTimeout = null;
        let uploadSource = 'local';
        let validationConfig = {};
        let slowHintTimer = null;
        let draggedCardId = null;
        let currentView = localStorage.getItem('mediaAdminView') || 'grid';
        let lastMediaData = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('viewToggleGrid').classList.toggle('active', currentView === 'grid');
            document.getElementById('viewToggleList').classList.toggle('active', currentView === 'list');
            loadStats();
            loadMedia();
            loadValidationConfig();
            @if($tagsEnabled)
            loadTags();
            @endif
        });

        function loadValidationConfig() {
            fetch(`${adminApiUrl}/validation-config`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                validationConfig = data.validation || {};
            })
            .catch(() => {});
        }

        function showModalError(errorId, message) {
            const el = document.getElementById(errorId);
            if (el) {
                el.textContent = message;
                el.style.display = 'block';
            }
        }

        function hideModalError(errorId) {
            const el = document.getElementById(errorId);
            if (el) {
                el.textContent = '';
                el.style.display = 'none';
            }
        }

        function setButtonLoading(btnId, contentId, isLoading, originalText) {
            const btn = document.getElementById(btnId);
            const content = document.getElementById(contentId);
            if (!btn || !content) return;
            if (isLoading) {
                btn.disabled = true;
                content.innerHTML = '<span class="btn-spinner"></span>';
            } else {
                btn.disabled = false;
                content.innerHTML = originalText;
            }
        }

        // Generic loading for any button element (auto-detects light/dark style)
        function setBtnLoading(btn, isLoading) {
            if (!btn) return;
            if (isLoading) {
                btn.disabled = true;
                btn.dataset.originalHtml = btn.innerHTML;
                const isDark = btn.classList.contains('btn-primary') || btn.classList.contains('btn-danger');
                const border = isDark ? 'rgba(255,255,255,0.35)' : 'rgba(0,0,0,0.15)';
                const top    = isDark ? 'white' : '#4a5568';
                btn.innerHTML = `<span style="display:inline-block;width:13px;height:13px;border:2px solid ${border};border-top-color:${top};border-radius:50%;animation:spin 0.8s linear infinite;vertical-align:middle;"></span>`;
            } else {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalHtml || '';
                delete btn.dataset.originalHtml;
            }
        }

        function showToast(message, type = 'error') {
            const toast = document.getElementById('toastNotification');
            toast.textContent = message;
            toast.className = `toast-notification toast-${type} active`;
            setTimeout(() => toast.classList.remove('active'), 3500);
        }

        function setView(view) {
            currentView = view;
            localStorage.setItem('mediaAdminView', view);
            document.getElementById('viewToggleGrid').classList.toggle('active', view === 'grid');
            document.getElementById('viewToggleList').classList.toggle('active', view === 'list');
            displayMedia(lastMediaData);
        }

        // ─── Drag-and-drop reordering ───────────────────────────────────────

        function initDragAndDrop() {
            document.querySelectorAll('[data-media-id]').forEach(card => {
                const handle = card.querySelector('.drag-handle');
                if (!handle) return;

                handle.addEventListener('mousedown', () => {
                    card.setAttribute('draggable', 'true');
                });

                card.addEventListener('dragstart', () => {
                    draggedCardId = parseInt(card.dataset.mediaId);
                    card.classList.add('dragging');
                });

                card.addEventListener('dragend', () => {
                    card.removeAttribute('draggable');
                    card.classList.remove('dragging');
                    draggedCardId = null;
                    clearDragOverClasses();
                });

                card.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const targetId = parseInt(card.dataset.mediaId);
                    if (!draggedCardId || targetId === draggedCardId) return;
                    clearDragOverClasses();

                    if (currentView === 'list') {
                        const rect = card.getBoundingClientRect();
                        card.classList.add(e.clientY < rect.top + rect.height / 2 ? 'drag-over-before' : 'drag-over-after');
                    } else {
                        card.classList.add('drag-over');
                    }
                });

                card.addEventListener('dragleave', (e) => {
                    if (!card.contains(e.relatedTarget)) {
                        card.classList.remove('drag-over', 'drag-over-before', 'drag-over-after');
                    }
                });

                card.addEventListener('drop', (e) => {
                    e.preventDefault();
                    clearDragOverClasses();
                    const targetId = parseInt(card.dataset.mediaId);
                    if (!draggedCardId || draggedCardId === targetId) return;

                    const rect = card.getBoundingClientRect();
                    const insertBefore = e.clientY < rect.top + rect.height / 2;
                    moveCardInDom(draggedCardId, targetId, insertBefore);
                    saveOrder();
                });
            });
        }

        function clearDragOverClasses() {
            document.querySelectorAll('[data-media-id]')
                .forEach(c => c.classList.remove('drag-over', 'drag-over-before', 'drag-over-after'));
        }

        function moveCardInDom(srcId, targetId, insertBefore) {
            const container = document.querySelector('.media-items-container');
            if (!container) return;
            const src = container.querySelector(`[data-media-id="${srcId}"]`);
            const target = container.querySelector(`[data-media-id="${targetId}"]`);
            if (!src || !target) return;
            insertBefore ? container.insertBefore(src, target) : target.after(src);
        }

        function saveOrder() {
            const container = document.querySelector('.media-items-container');
            if (!container) return;

            const ids = Array.from(container.querySelectorAll('[data-media-id]'))
                .map(c => parseInt(c.dataset.mediaId));

            setOrderSaving(true);

            fetch(`${adminApiUrl}/media/reorder`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ids,
                    page: currentFilters.page,
                    per_page: currentFilters.per_page
                })
            })
            .then(response => response.json())
            .then(data => {
                setOrderSaving(false);
                if (!data.success) showToast('Failed to save order', 'error');
            })
            .catch(() => {
                setOrderSaving(false);
                showToast('Failed to save order', 'error');
            });
        }

        function setOrderSaving(isSaving) {
            const el = document.getElementById('orderSavingIndicator');
            if (el) el.style.display = isSaving ? 'flex' : 'none';
        }

        function loadStats() {
            fetch(`${adminApiUrl}/stats`, {
                headers: {
                    'Authorization': `Bearer ${csrfToken}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalMediaStat').textContent = `${data.total} Total Media`;
                document.getElementById('count-all').textContent = data.total;

                // Update type counts
                Object.entries(data.by_type).forEach(([type, count]) => {
                    const el = document.getElementById(`count-${type}`);
                    if (el) el.textContent = count;
                });

                // Update source counts
                document.getElementById('count-source-local').textContent = data.by_source.local;
                document.getElementById('count-source-external').textContent = data.by_source.external;
            });
        }

        function loadTags() {
            fetch(`${adminApiUrl}/tags`, {
                headers: {
                    'Authorization': `Bearer ${csrfToken}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                allTags = data;
                displayTagsFilter(data);
            });
        }

        function displayTagsFilter(tags) {
            const container = document.getElementById('tagsList');
            if (!container) return;

            if (tags.length === 0) {
                container.innerHTML = '<div style="padding: 0.5rem; color: #718096; font-size: 0.75rem;">No tags yet</div>';
                return;
            }

            container.innerHTML = tags.map(tag => `
                <div class="filter-option" data-filter-type="tag" data-value="${tag.slug}" onclick="applyFilter('tag', '${tag.slug}')">
                    <span>${tag.name}</span>
                    <span class="filter-count">${tag.media_resources_count}</span>
                </div>
            `).join('');
        }

        function applyFilter(type, value) {
            currentFilters[type] = value;
            currentFilters.page = 1;

            // Update active state
            document.querySelectorAll(`.filter-option[data-filter-type="${type}"]`).forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.filter-option[data-filter-type="${type}"][data-value="${value}"]`)?.classList.add('active');

            loadMedia();
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = document.getElementById('searchInput').value;
                currentFilters.page = 1;
                loadMedia();
            }, 500);
        }

        function loadMedia(onComplete) {
            const params = new URLSearchParams();
            Object.entries(currentFilters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            fetch(`${adminApiUrl}/media?${params}`, {
                headers: {
                    'Authorization': `Bearer ${csrfToken}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayMedia(data.data);
                displayPagination(data);
                if (onComplete) onComplete();
            })
            .catch(error => {
                document.getElementById('mediaContainer').innerHTML = `
                    <div class="empty-state">
                        <p>Error loading media. Please try again.</p>
                    </div>
                `;
                if (onComplete) onComplete();
            });
        }

        function displayMedia(media) {
            lastMediaData = media;
            const container = document.getElementById('mediaContainer');

            if (media.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p>No media found</p>
                    </div>
                `;
                return;
            }

            if (currentView === 'list') {
                container.innerHTML = `
                    <div class="media-list media-items-container">
                        ${media.map(item => createMediaRow(item)).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="media-grid media-items-container">
                        ${media.map(item => createMediaCard(item)).join('')}
                    </div>
                `;
            }
            initDragAndDrop();
        }

        function createMediaCard(media) {
            const isSelected = selectedMedia.has(media.id);
            const thumbnailUrl = media.thumbnail_url || getMediaPreview(media);
            const fileSize = formatFileSize(media.file_size);

            return `
                <div class="media-card ${isSelected ? 'selected' : ''}" data-media-id="${media.id}">
                    <div class="drag-handle" title="Drag to reorder">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <circle cx="3.5" cy="2" r="1.2"/><circle cx="8.5" cy="2" r="1.2"/>
                            <circle cx="3.5" cy="6" r="1.2"/><circle cx="8.5" cy="6" r="1.2"/>
                            <circle cx="3.5" cy="10" r="1.2"/><circle cx="8.5" cy="10" r="1.2"/>
                        </svg>
                    </div>
                    <input type="checkbox" class="media-checkbox" ${isSelected ? 'checked' : ''} onchange="toggleMediaSelection(${media.id})">
                    <div class="media-preview">
                        ${thumbnailUrl ? `<img src="${thumbnailUrl}" alt="${media.name}">` : `
                            <div class="media-preview-icon">${getMediaIcon(media.type)}</div>
                        `}
                    </div>
                    <div class="media-info">
                        <div class="media-name" title="${media.display_name}">${media.display_name}</div>
                        <div class="media-meta">
                            <span class="type-badge ${media.type}">${media.type}</span>
                            ${fileSize ? `<span class="file-size">${fileSize}</span>` : ''}
                        </div>
                        ${media.tags && media.tags.length > 0 ? `
                            <div class="media-tags">
                                ${media.tags.map(tag => `<span class="tag">${tag.name}</span>`).join('')}
                            </div>
                        ` : ''}
                        <div class="media-actions">
                            <button class="action-btn" onclick="openEditModal(${media.id}, this)">Edit</button>
                            <button class="action-btn" onclick="viewMedia(${media.id})">View</button>
                            <button class="action-btn" onclick="deleteMedia(${media.id}, this)">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        }

        function createMediaRow(media) {
            const isSelected = selectedMedia.has(media.id);
            const thumbnailUrl = media.thumbnail_url || getMediaPreview(media);
            const fileSize = formatFileSize(media.file_size);

            return `
                <div class="media-row ${isSelected ? 'selected' : ''}" data-media-id="${media.id}">
                    <div class="drag-handle" title="Drag to reorder">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <circle cx="3.5" cy="2" r="1.2"/><circle cx="8.5" cy="2" r="1.2"/>
                            <circle cx="3.5" cy="6" r="1.2"/><circle cx="8.5" cy="6" r="1.2"/>
                            <circle cx="3.5" cy="10" r="1.2"/><circle cx="8.5" cy="10" r="1.2"/>
                        </svg>
                    </div>
                    <input type="checkbox" class="media-checkbox" ${isSelected ? 'checked' : ''} onchange="toggleMediaSelection(${media.id})">
                    <div class="row-thumb">
                        ${thumbnailUrl
                            ? `<img src="${thumbnailUrl}" alt="${media.display_name}">`
                            : `<div class="row-thumb-icon">${getMediaIcon(media.type)}</div>`}
                    </div>
                    <div class="row-info">
                        <div class="row-name" title="${media.display_name}">${media.display_name}</div>
                        ${media.description ? `<div class="row-desc">${media.description}</div>` : ''}
                    </div>
                    <div class="row-meta">
                        <span class="type-badge ${media.type}">${media.type}</span>
                        ${fileSize ? `<span class="file-size">${fileSize}</span>` : ''}
                    </div>
                    <div class="row-actions">
                        <button class="action-btn" onclick="openEditModal(${media.id}, this)">Edit</button>
                        <button class="action-btn" onclick="viewMedia(${media.id})">View</button>
                        <button class="action-btn" style="color: #e53e3e;" onclick="deleteMedia(${media.id}, this)">Delete</button>
                    </div>
                </div>
            `;
        }

        function getMediaPreview(media) {
            if (media.thumbnail_url) return media.thumbnail_url;
            if (media.type === 'image' && media.media_url) return media.media_url;
            return '';
        }

        function getMediaIcon(type) {
            const icons = {
                video: '🎥',
                audio: '🎵',
                document: '📄',
                image: '🖼️'
            };
            return icons[type] || '📁';
        }

        function formatFileSize(bytes) {
            if (!bytes) return '';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
        }

        function displayPagination(data) {
            const container = document.getElementById('paginationContainer');
            if (data.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';

            // Previous button
            html += `<button class="page-btn" ${data.current_page === 1 ? 'disabled' : ''} onclick="changePage(${data.current_page - 1})">Previous</button>`;

            // Page numbers
            for (let i = 1; i <= data.last_page; i++) {
                if (i === 1 || i === data.last_page || (i >= data.current_page - 2 && i <= data.current_page + 2)) {
                    html += `<button class="page-btn ${i === data.current_page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
                } else if (i === data.current_page - 3 || i === data.current_page + 3) {
                    html += `<span>...</span>`;
                }
            }

            // Next button
            html += `<button class="page-btn" ${data.current_page === data.last_page ? 'disabled' : ''} onclick="changePage(${data.current_page + 1})">Next</button>`;

            html += '</div>';
            container.innerHTML = html;
        }

        function changePage(page) {
            currentFilters.page = page;
            loadMedia();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function refreshMedia(btn) {
            setBtnLoading(btn, true);
            loadStats();
            loadMedia(() => setBtnLoading(btn, false));
            @if($tagsEnabled)
            loadTags();
            @endif
        }

        function toggleMediaSelection(mediaId) {
            if (selectedMedia.has(mediaId)) {
                selectedMedia.delete(mediaId);
            } else {
                selectedMedia.add(mediaId);
            }
            updateBulkActionsBar();
            updateMediaCardSelection(mediaId);
        }

        function updateMediaCardSelection(mediaId) {
            const card = document.querySelector(`[data-media-id="${mediaId}"]`);
            if (card) {
                if (selectedMedia.has(mediaId)) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        }

        function updateBulkActionsBar() {
            const bar = document.getElementById('bulkActionsBar');
            const count = document.getElementById('selectedCount');

            count.textContent = `${selectedMedia.size} selected`;

            if (selectedMedia.size > 0) {
                bar.classList.add('active');
            } else {
                bar.classList.remove('active');
            }
        }

        function clearSelection() {
            selectedMedia.clear();
            document.querySelectorAll('[data-media-id]').forEach(item => {
                item.classList.remove('selected');
                const cb = item.querySelector('.media-checkbox');
                if (cb) cb.checked = false;
            });
            updateBulkActionsBar();
        }

        function bulkDelete(btn) {
            if (!confirm(`Are you sure you want to delete ${selectedMedia.size} media items? This action cannot be undone.`)) {
                return;
            }

            setBtnLoading(btn, true);
            const ids = Array.from(selectedMedia);

            fetch(`${apiBaseUrl}/bulk`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    clearSelection();
                    refreshMedia();
                } else {
                    setBtnLoading(btn, false);
                    showToast(data.message || 'Error deleting media', 'error');
                }
            })
            .catch(() => {
                setBtnLoading(btn, false);
                showToast('Error deleting media', 'error');
            });
        }

        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
            uploadTags = [];
            uploadSource = '';

            // Hide form fields initially and reset form
            document.getElementById('uploadFormFields').style.display = 'none';
            document.getElementById('uploadForm').reset();
            document.getElementById('uploadSubmitBtn').disabled = true;

            // Uncheck all source radios
            document.querySelectorAll('input[name="uploadSource"]').forEach(radio => {
                radio.checked = false;
            });

            // Reset thumbnail options
            document.getElementById('uploadAutoThumbnail').checked = true;
            document.getElementById('autoThumbnailGroup').style.display = 'none';
            document.getElementById('advancedThumbnailContent').style.display = 'none';
            document.getElementById('advancedThumbnailIcon').textContent = '▶';
            document.getElementById('uploadThumbnailFile').value = '';
            document.getElementById('uploadThumbnailUrl').value = '';

            // Disable advanced options when auto-thumbnail is checked
            setAdvancedThumbnailState(false);
        }

        function closeUploadModal() {
            clearTimeout(slowHintTimer);
            document.getElementById('slowUploadHint').style.display = 'none';
            document.getElementById('uploadBtnContent').textContent = 'Upload';
            hideModalError('uploadModalError');

            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('uploadForm').reset();
            uploadTags = [];
            uploadSource = '';

            // Hide form fields
            document.getElementById('uploadFormFields').style.display = 'none';
            document.getElementById('uploadSubmitBtn').disabled = true;

            // Reset thumbnail options
            document.getElementById('autoThumbnailGroup').style.display = 'none';
            document.getElementById('advancedThumbnailContent').style.display = 'none';
            document.getElementById('advancedThumbnailIcon').textContent = '▶';
        }

        function switchUploadSource(source) {
            uploadSource = source;

            // Show form fields with smooth transition
            const formFields = document.getElementById('uploadFormFields');
            const submitBtn = document.getElementById('uploadSubmitBtn');

            if (formFields.style.display === 'none') {
                formFields.style.display = 'block';
                submitBtn.disabled = false;
                // Optional: Add animation
                formFields.style.opacity = '0';
                setTimeout(() => {
                    formFields.style.transition = 'opacity 0.3s ease-in-out';
                    formFields.style.opacity = '1';
                }, 10);
            }

            const fileGroup = document.getElementById('uploadFileGroup');
            const urlGroup = document.getElementById('uploadUrlGroup');
            const fileInput = document.getElementById('uploadFile');
            const urlInput = document.getElementById('uploadUrl');

            if (source === 'local') {
                fileGroup.style.display = 'block';
                urlGroup.style.display = 'none';
                fileInput.required = true;
                urlInput.required = false;
            } else {
                fileGroup.style.display = 'none';
                urlGroup.style.display = 'block';
                fileInput.required = false;
                urlInput.required = true;
            }

            // Update visibility based on media type
            handleTypeChange();
        }

        function toggleAutoThumbnail() {
            const autoChecked = document.getElementById('uploadAutoThumbnail').checked;

            if (autoChecked) {
                // Disable and collapse advanced options
                setAdvancedThumbnailState(false);
                document.getElementById('advancedThumbnailContent').style.display = 'none';
                document.getElementById('advancedThumbnailIcon').textContent = '▶';
            } else {
                // Enable advanced options
                setAdvancedThumbnailState(true);
            }
        }

        function setAdvancedThumbnailState(enabled) {
            const thumbnailFile = document.getElementById('uploadThumbnailFile');
            const thumbnailUrl = document.getElementById('uploadThumbnailUrl');
            const advancedLabel = document.querySelector('[onclick="toggleAdvancedThumbnail()"]');

            if (enabled) {
                thumbnailFile.disabled = false;
                thumbnailUrl.disabled = false;
                advancedLabel.style.opacity = '1';
                advancedLabel.style.cursor = 'pointer';
            } else {
                thumbnailFile.disabled = true;
                thumbnailUrl.disabled = true;
                thumbnailFile.value = '';
                thumbnailUrl.value = '';
                advancedLabel.style.opacity = '0.5';
                advancedLabel.style.cursor = 'not-allowed';
            }
        }

        function toggleAdvancedThumbnail() {
            // Don't allow toggle if auto-thumbnail is checked for local images
            const autoThumbnailGroup = document.getElementById('autoThumbnailGroup');
            const autoChecked = document.getElementById('uploadAutoThumbnail').checked;

            if (autoThumbnailGroup.style.display === 'block' && autoChecked) {
                return; // Disabled, don't toggle
            }

            const content = document.getElementById('advancedThumbnailContent');
            const icon = document.getElementById('advancedThumbnailIcon');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.textContent = '▼';
            } else {
                content.style.display = 'none';
                icon.textContent = '▶';
            }
        }

        function handleTagInput(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                const input = event.target;
                const value = input.value.trim();
                if (value && !uploadTags.includes(value)) {
                    uploadTags.push(value);
                    updateUploadTagsDisplay();
                }
                input.value = '';
            }
        }

        function updateUploadTagsDisplay() {
            const container = document.getElementById('uploadTagsContainer');
            const input = document.getElementById('uploadTagInput');

            const tagsHtml = uploadTags.map(tag => `
                <div class="tag-item">
                    <span>${tag}</span>
                    <span class="tag-remove" onclick="removeUploadTag('${tag}')">×</span>
                </div>
            `).join('');

            container.innerHTML = tagsHtml + container.querySelector('.tag-input').outerHTML;
        }

        function removeUploadTag(tag) {
            uploadTags = uploadTags.filter(t => t !== tag);
            updateUploadTagsDisplay();
        }

        function uploadMedia(event) {
            event.preventDefault();
            hideModalError('uploadModalError');

            const type = document.getElementById('uploadType').value;
            const formData = new FormData();
            formData.append('type', type);
            formData.append('source', uploadSource);
            formData.append('name', document.getElementById('uploadName').value);
            formData.append('description', document.getElementById('uploadDescription').value || '');

            if (uploadSource === 'local') {
                const fileInput = document.getElementById('uploadFile');
                if (!fileInput.files[0]) {
                    showModalError('uploadModalError', 'Please select a file to upload');
                    return;
                }

                const file = fileInput.files[0];
                const typeConfig = validationConfig[type];
                if (typeConfig) {
                    if (typeConfig.max_size && file.size > typeConfig.max_size * 1024) {
                        const maxMB = (typeConfig.max_size / 1024).toFixed(0);
                        showModalError('uploadModalError', `File size exceeds the maximum allowed size of ${maxMB}MB`);
                        return;
                    }
                    if (typeConfig.mimes && typeConfig.mimes.length > 0) {
                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!typeConfig.mimes.includes(ext)) {
                            showModalError('uploadModalError', `Invalid file type. Allowed types: ${typeConfig.mimes.join(', ')}`);
                            return;
                        }
                    }
                }

                formData.append('file', file);

                // Handle thumbnail options for local files
                const autoThumbnail = document.getElementById('uploadAutoThumbnail').checked;
                if (type === 'image') {
                    formData.append('generate_thumbnail', autoThumbnail ? '1' : '0');
                }

                // Add custom thumbnail if provided
                const thumbnailFile = document.getElementById('uploadThumbnailFile').files[0];
                if (thumbnailFile) {
                    formData.append('thumbnail_file', thumbnailFile);
                }

                const thumbnailUrl = document.getElementById('uploadThumbnailUrl').value;
                if (thumbnailUrl) {
                    formData.append('thumbnail_url', thumbnailUrl);
                }
            } else {
                const url = document.getElementById('uploadUrl').value;
                if (!url) {
                    showModalError('uploadModalError', 'Please enter a URL');
                    return;
                }
                formData.append('url', url);

                // Add custom thumbnail if provided
                const thumbnailFile = document.getElementById('uploadThumbnailFile').files[0];
                if (thumbnailFile) {
                    formData.append('thumbnail_file', thumbnailFile);
                }

                const thumbnailUrl = document.getElementById('uploadThumbnailUrl').value;
                if (thumbnailUrl) {
                    formData.append('thumbnail_url', thumbnailUrl);
                }
            }

            uploadTags.forEach(tag => formData.append('tags[]', tag));

            setButtonLoading('uploadSubmitBtn', 'uploadBtnContent', true, 'Upload');
            slowHintTimer = setTimeout(() => {
                document.getElementById('slowUploadHint').style.display = 'block';
            }, 10000);

            fetch(`${apiBaseUrl}/upload`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearTimeout(slowHintTimer);
                document.getElementById('slowUploadHint').style.display = 'none';
                setButtonLoading('uploadSubmitBtn', 'uploadBtnContent', false, 'Upload');

                if (data.success || data.id) {
                    closeUploadModal();
                    refreshMedia();
                } else {
                    showModalError('uploadModalError', data.message || 'Error uploading media');
                }
            })
            .catch(error => {
                clearTimeout(slowHintTimer);
                document.getElementById('slowUploadHint').style.display = 'none';
                setButtonLoading('uploadSubmitBtn', 'uploadBtnContent', false, 'Upload');
                console.error('Upload error:', error);
                showModalError('uploadModalError', 'Error uploading media. Please try again.');
            });
        }

        function handleTypeChange() {
            const type = document.getElementById('uploadType').value;
            const autoThumbnailGroup = document.getElementById('autoThumbnailGroup');

            // Show auto-thumbnail checkbox only for local images
            if (type === 'image' && uploadSource === 'local') {
                autoThumbnailGroup.style.display = 'block';

                // Check if auto-thumbnail is enabled
                const autoChecked = document.getElementById('uploadAutoThumbnail').checked;
                setAdvancedThumbnailState(!autoChecked);

                if (autoChecked) {
                    // Collapse advanced options when auto is enabled
                    document.getElementById('advancedThumbnailContent').style.display = 'none';
                    document.getElementById('advancedThumbnailIcon').textContent = '▶';
                }
            } else {
                autoThumbnailGroup.style.display = 'none';
                // Always enable advanced options for non-local-image uploads
                setAdvancedThumbnailState(true);
            }
        }

        function openEditModal(mediaId, btn) {
            setBtnLoading(btn, true);
            fetch(`${apiBaseUrl}/${mediaId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(response => {
                setBtnLoading(btn, false);
                const data = response.data;
                document.getElementById('editMediaId').value = data.id;
                document.getElementById('editName').value = data.name;
                document.getElementById('editDescription').value = data.description || '';
                editTags = data.tags;
                updateEditTagsDisplay();
                document.getElementById('editModal').classList.add('active');
            })
            .catch(() => {
                setBtnLoading(btn, false);
                showToast('Failed to load media details', 'error');
            });
        }

        function closeEditModal() {
            hideModalError('editModalError');
            document.getElementById('editBtnContent').textContent = 'Save Changes';
            document.getElementById('editModal').classList.remove('active');
            editTags = [];
        }

        function handleEditTagInput(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                const input = event.target;
                const value = input.value.trim();
                if (value && !editTags.includes(value)) {
                    editTags.push(value);
                    updateEditTagsDisplay();
                }
                input.value = '';
            }
        }

        function updateEditTagsDisplay() {
            const container = document.getElementById('editTagsContainer');
            const input = document.getElementById('editTagInput');

            const tagsHtml = editTags.map(tag => `
                <div class="tag-item">
                    <span>${tag}</span>
                    <span class="tag-remove" onclick="removeEditTag('${tag}')">×</span>
                </div>
            `).join('');

            container.innerHTML = tagsHtml + container.querySelector('.tag-input').outerHTML;
        }

        function removeEditTag(tag) {
            editTags = editTags.filter(t => t !== tag);
            updateEditTagsDisplay();
        }

        function saveEdit(event) {
            event.preventDefault();
            hideModalError('editModalError');

            const mediaId = document.getElementById('editMediaId').value;
            const data = {
                name: document.getElementById('editName').value,
                description: document.getElementById('editDescription').value,
            };

            setButtonLoading('editSubmitBtn', 'editBtnContent', true, 'Save Changes');

            // Update basic info
            fetch(`${adminApiUrl}/media/${mediaId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(() => {
                // Update tags
                return fetch(`${adminApiUrl}/media/${mediaId}/tags`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ tags: editTags })
                });
            })
            .then(response => response.json())
            .then(data => {
                setButtonLoading('editSubmitBtn', 'editBtnContent', false, 'Save Changes');
                if (data.success) {
                    closeEditModal();
                    refreshMedia();
                } else {
                    showModalError('editModalError', data.message || 'Error updating media');
                }
            })
            .catch(error => {
                setButtonLoading('editSubmitBtn', 'editBtnContent', false, 'Save Changes');
                showModalError('editModalError', 'Error updating media. Please try again.');
            });
        }

        function viewMedia(mediaId) {
            // Fetch media details and open in new tab
            fetch(`${apiBaseUrl}/${mediaId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(response => {
                const data = response.data; // Extract nested data object
                if (data.media_url) {
                    window.open(data.media_url, '_blank');
                } else if (data.url) {
                    window.open(data.url, '_blank');
                }
            });
        }

        function deleteMedia(mediaId, btn) {
            if (!confirm('Are you sure you want to delete this media? This action cannot be undone.')) {
                return;
            }

            setBtnLoading(btn, true);

            fetch(`${apiBaseUrl}/${mediaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    refreshMedia();
                } else {
                    setBtnLoading(btn, false);
                    showToast(data.message || 'Error deleting media', 'error');
                }
            })
            .catch(() => {
                setBtnLoading(btn, false);
                showToast('Error deleting media', 'error');
            });
        }

        // Close modals on outside click
        document.getElementById('uploadModal').addEventListener('click', function(e) {
            if (e.target === this) closeUploadModal();
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>
