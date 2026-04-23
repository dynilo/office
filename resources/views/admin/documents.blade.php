@extends('admin.layout')

@section('pageStyles')
    <style>
        .documents-workspace {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(22rem, 1.05fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .documents-panel,
        .document-upload {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.84);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .documents-panel h3,
        .document-upload h3 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }

        .documents-panel p,
        .document-upload p,
        .empty-state {
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .document-toolbar {
            display: grid;
            grid-template-columns: minmax(12rem, 1fr) auto;
            gap: 0.7rem;
            margin: 1rem 0;
        }

        .field {
            display: grid;
            gap: 0.35rem;
        }

        .field label {
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .field input,
        .field textarea {
            width: 100%;
            padding: 0.72rem 0.8rem;
            border: 1px solid rgba(20, 33, 61, 0.14);
            border-radius: 0.85rem;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.74);
            font: inherit;
        }

        .field textarea {
            min-height: 6.5rem;
            resize: vertical;
            font-family: "Courier New", monospace;
            font-size: 0.88rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.72rem 1rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: #fffdf7;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .button.secondary {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.72);
        }

        .document-list,
        .chunk-list {
            display: grid;
            gap: 0.75rem;
        }

        .document-card,
        .chunk-card {
            display: grid;
            gap: 0.65rem;
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(20, 33, 61, 0.09);
            border-radius: 1rem;
            color: inherit;
            text-align: left;
            background: rgba(255, 255, 255, 0.62);
        }

        .document-card {
            cursor: pointer;
        }

        .document-card.is-selected {
            border-color: rgba(196, 106, 45, 0.45);
            background: rgba(241, 215, 196, 0.42);
        }

        .document-card h4,
        .chunk-card h4 {
            margin: 0;
            font-size: 1.02rem;
            overflow-wrap: anywhere;
        }

        .document-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .pill {
            display: inline-flex;
            padding: 0.28rem 0.58rem;
            border-radius: 999px;
            color: var(--ink-soft);
            background: rgba(20, 33, 61, 0.06);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .pill.good {
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .document-detail {
            position: sticky;
            top: 1rem;
            align-self: start;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .detail-item {
            padding: 0.8rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.58);
        }

        .detail-item span {
            display: block;
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .detail-item strong {
            display: block;
            margin-top: 0.25rem;
            overflow-wrap: anywhere;
        }

        .raw-text,
        .chunk-content {
            overflow: auto;
            max-height: 16rem;
            padding: 1rem;
            border-radius: 1rem;
            color: #233047;
            background: rgba(20, 33, 61, 0.06);
            font-family: "Courier New", monospace;
            font-size: 0.86rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }

        .document-upload {
            margin-top: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .form-grid .span-2 {
            grid-column: 1 / -1;
        }

        .notice {
            display: none;
            margin-top: 1rem;
            padding: 0.75rem 0.9rem;
            border-radius: 0.9rem;
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .notice.is-error {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .notice.is-visible {
            display: block;
        }

        @media (max-width: 1100px) {
            .documents-workspace,
            .document-toolbar,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .document-detail {
                position: static;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Document knowledge active</span>
        <h2>Ingest documents and inspect knowledge chunks.</h2>
        <p>
            Upload supported text documents through the existing ingestion API, inspect persisted raw text,
            and trigger deterministic knowledge extraction to view chunk metadata.
        </p>
    </section>

    <section class="documents-workspace" aria-label="Document and knowledge workspace">
        <div class="documents-panel">
            <h3>Documents</h3>
            <p>Filter ingested documents and select one to inspect extracted text and knowledge chunks.</p>

            <div class="document-toolbar">
                <div class="field">
                    <label for="document-search">Search</label>
                    <input id="document-search" type="search" placeholder="Title, MIME type, metadata">
                </div>
                <button class="button secondary" type="button" id="document-refresh">Reset filters</button>
            </div>

            <div id="document-list" class="document-list">
                @forelse ($documents as $document)
                    <button
                        type="button"
                        class="document-card {{ $loop->first ? 'is-selected' : '' }}"
                        data-action="inspect"
                        data-document-id="{{ $document['id'] }}"
                    >
                        <div class="document-meta">
                            <span class="pill">{{ $document['mime_type'] }}</span>
                            <span class="pill good">{{ count($document['knowledge_items']) }} chunks</span>
                        </div>
                        <h4>{{ $document['title'] }}</h4>
                        <p>{{ $document['storage_path'] }}</p>
                    </button>
                @empty
                    <p class="empty-state">No documents have been ingested yet. Upload a text document to begin.</p>
                @endforelse
            </div>
        </div>

        <aside class="documents-panel document-detail" aria-live="polite">
            <h3>Document detail</h3>
            <div id="document-detail">
                @if (count($documents) > 0)
                    @php($selectedDocument = $documents[0])
                    <h4>{{ $selectedDocument['title'] }}</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><span>MIME</span><strong>{{ $selectedDocument['mime_type'] }}</strong></div>
                        <div class="detail-item"><span>Size</span><strong>{{ $selectedDocument['size_bytes'] }} bytes</strong></div>
                        <div class="detail-item"><span>Disk</span><strong>{{ $selectedDocument['storage_disk'] }}</strong></div>
                        <div class="detail-item"><span>Chunks</span><strong>{{ count($selectedDocument['knowledge_items']) }}</strong></div>
                    </div>
                    <button class="button secondary" type="button" data-action="extract" data-document-id="{{ $selectedDocument['id'] }}">Extract knowledge</button>
                    <h4>Extracted raw text</h4>
                    <pre class="raw-text">{{ $selectedDocument['raw_text'] ?: 'No raw text extracted.' }}</pre>
                    <h4>Knowledge chunks</h4>
                    <div class="chunk-list">
                        @forelse ($selectedDocument['knowledge_items'] as $item)
                            <article class="chunk-card">
                                <div class="document-meta">
                                    <span class="pill">chunk {{ ($item['metadata']['chunk_index'] ?? 0) + 1 }}</span>
                                    <span class="pill">{{ $item['metadata']['character_count'] ?? strlen($item['content']) }} chars</span>
                                </div>
                                <h4>{{ $item['title'] }}</h4>
                                <pre class="chunk-content">{{ $item['content'] }}</pre>
                            </article>
                        @empty
                            <p class="empty-state">No knowledge chunks exist yet. Run extraction for this document.</p>
                        @endforelse
                    </div>
                @else
                    <p class="empty-state">Select or upload a document to inspect its text and knowledge chunks.</p>
                @endif
            </div>
        </aside>
    </section>

    <section class="document-upload" aria-label="Upload document form">
        <h3>Upload document</h3>
        <p>Supported files use the existing ingestion contract: plain text, Markdown, CSV, and JSON.</p>

        <form id="document-upload-form" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field">
                    <label for="document-file">File</label>
                    <input id="document-file" name="file" type="file" accept=".txt,.md,.csv,.json,text/plain,text/markdown,text/csv,application/json" required>
                </div>
                <div class="field">
                    <label for="document-title">Title</label>
                    <input id="document-title" name="title" type="text" maxlength="255" placeholder="Research notes">
                </div>
                <div class="field span-2">
                    <label for="document-metadata">Metadata JSON</label>
                    <textarea id="document-metadata" name="metadata">{
  "source": "admin",
  "category": "notes"
}</textarea>
                </div>
            </div>

            <button class="button" type="submit">Upload document</button>
            <div id="document-notice" class="notice" role="status"></div>
        </form>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            const bootstrap = window.OfficeAdmin || {};
            const endpoints = bootstrap.documentKnowledge || {};
            let documents = Array.isArray(bootstrap.initialDocuments) ? bootstrap.initialDocuments : [];
            let selectedDocumentId = documents[0]?.id || null;

            const list = document.querySelector('#document-list');
            const detail = document.querySelector('#document-detail');
            const search = document.querySelector('#document-search');
            const reset = document.querySelector('#document-refresh');
            const form = document.querySelector('#document-upload-form');
            const metadataInput = document.querySelector('#document-metadata');
            const notice = document.querySelector('#document-notice');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const setNotice = (message, isError = false) => {
                notice.textContent = message;
                notice.classList.toggle('is-error', isError);
                notice.classList.add('is-visible');
            };

            const normalizeDocument = (document) => ({
                ...document,
                knowledge_items: Array.isArray(document.knowledge_items) ? document.knowledge_items : [],
                metadata: document.metadata || {},
            });

            const upsertDocument = (document) => {
                const normalized = normalizeDocument(document);
                const index = documents.findIndex((candidate) => candidate.id === normalized.id);

                if (index === -1) {
                    documents = [normalized, ...documents];
                    return normalized;
                }

                documents[index] = normalized;
                return normalized;
            };

            const filteredDocuments = () => {
                const term = search.value.trim().toLowerCase();

                return documents.filter((document) => {
                    const haystack = [
                        document.title,
                        document.mime_type,
                        document.storage_path,
                        JSON.stringify(document.metadata || {}),
                    ].join(' ').toLowerCase();

                    return !term || haystack.includes(term);
                });
            };

            const renderDocuments = () => {
                const visible = filteredDocuments();

                if (!visible.length) {
                    list.innerHTML = '<p class="empty-state">No documents match the current filter.</p>';
                    return;
                }

                list.innerHTML = visible.map((document) => `
                    <button type="button" class="document-card ${document.id === selectedDocumentId ? 'is-selected' : ''}" data-action="inspect" data-document-id="${escapeHtml(document.id)}">
                        <div class="document-meta">
                            <span class="pill">${escapeHtml(document.mime_type)}</span>
                            <span class="pill good">${escapeHtml(document.knowledge_items.length)} chunks</span>
                        </div>
                        <h4>${escapeHtml(document.title)}</h4>
                        <p>${escapeHtml(document.storage_path)}</p>
                    </button>
                `).join('');
            };

            const renderDetail = (document) => {
                if (!document) {
                    detail.innerHTML = '<p class="empty-state">Select or upload a document to inspect its text and knowledge chunks.</p>';
                    return;
                }

                detail.innerHTML = `
                    <h4>${escapeHtml(document.title)}</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><span>MIME</span><strong>${escapeHtml(document.mime_type)}</strong></div>
                        <div class="detail-item"><span>Size</span><strong>${escapeHtml(document.size_bytes)} bytes</strong></div>
                        <div class="detail-item"><span>Disk</span><strong>${escapeHtml(document.storage_disk)}</strong></div>
                        <div class="detail-item"><span>Chunks</span><strong>${escapeHtml(document.knowledge_items.length)}</strong></div>
                    </div>
                    <button class="button secondary" type="button" data-action="extract" data-document-id="${escapeHtml(document.id)}">Extract knowledge</button>
                    <h4>Extracted raw text</h4>
                    <pre class="raw-text">${escapeHtml(document.raw_text || 'No raw text extracted.')}</pre>
                    <h4>Knowledge chunks</h4>
                    <div class="chunk-list">${renderChunks(document.knowledge_items)}</div>
                `;
            };

            const renderChunks = (items) => {
                if (!items.length) {
                    return '<p class="empty-state">No knowledge chunks exist yet. Run extraction for this document.</p>';
                }

                return items.map((item) => `
                    <article class="chunk-card">
                        <div class="document-meta">
                            <span class="pill">chunk ${escapeHtml(Number(item.metadata?.chunk_index || 0) + 1)}</span>
                            <span class="pill">${escapeHtml(item.metadata?.character_count || item.content?.length || 0)} chars</span>
                        </div>
                        <h4>${escapeHtml(item.title)}</h4>
                        <pre class="chunk-content">${escapeHtml(item.content)}</pre>
                    </article>
                `).join('');
            };

            const extractKnowledge = async (documentId) => {
                const response = await fetch(`${endpoints.extractKnowledge}/${documentId}/extract-knowledge`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body.message || 'Knowledge extraction failed.');
                }

                const document = documents.find((candidate) => candidate.id === documentId);

                if (!document) {
                    return;
                }

                document.knowledge_items = body.data || [];
                selectedDocumentId = document.id;
                renderDocuments();
                renderDetail(document);
                setNotice(`Extracted ${document.knowledge_items.length} knowledge chunks.`);
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                let metadata;

                try {
                    metadata = JSON.parse(metadataInput.value || '{}');
                } catch (error) {
                    setNotice('Metadata JSON must be valid JSON.', true);
                    return;
                }

                const formData = new FormData(form);
                formData.delete('metadata');
                Object.entries(metadata).forEach(([key, value]) => {
                    formData.append(`metadata[${key}]`, typeof value === 'string' ? value : JSON.stringify(value));
                });

                const response = await fetch(endpoints.ingest, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    setNotice(body.message || 'Document upload failed.', true);
                    return;
                }

                const document = upsertDocument(body.data || body);
                selectedDocumentId = document.id;
                form.reset();
                metadataInput.value = '{\n  "source": "admin",\n  "category": "notes"\n}';
                renderDocuments();
                renderDetail(document);
                setNotice('Document uploaded and raw text extracted.');
            });

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action="inspect"]');

                if (!button) {
                    return;
                }

                selectedDocumentId = button.dataset.documentId;
                renderDocuments();
                renderDetail(documents.find((document) => document.id === selectedDocumentId));
            });

            detail.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-action="extract"]');

                if (!button) {
                    return;
                }

                try {
                    await extractKnowledge(button.dataset.documentId);
                } catch (error) {
                    setNotice(error.message, true);
                }
            });

            search.addEventListener('input', renderDocuments);
            reset.addEventListener('click', () => {
                search.value = '';
                renderDocuments();
            });

            renderDocuments();
            renderDetail(documents.find((document) => document.id === selectedDocumentId));
        })();
    </script>
@endsection
