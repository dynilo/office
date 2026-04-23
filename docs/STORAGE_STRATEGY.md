# Runtime Storage Strategy

Documents and artifacts are private runtime assets by default. This baseline makes disk and path intent explicit without adding cloud storage workflows.

## Environment

```dotenv
DOCUMENT_STORAGE_DISK=local
DOCUMENT_STORAGE_PREFIX=documents
DOCUMENT_STORAGE_ALLOWED_DISKS=local,s3
ARTIFACT_STORAGE_DISK=local
ARTIFACT_STORAGE_PREFIX=artifacts
ARTIFACT_STORAGE_ALLOWED_DISKS=local,s3,private
RUNTIME_STORAGE_DISALLOW_PUBLIC_IN_PRODUCTION=true
```

## Documents

Document ingestion stores uploaded files under:

```text
{DOCUMENT_STORAGE_PREFIX}/YYYY/MM/DD/{ulid}_{safe-original-filename}
```

The database records the selected disk and relative path in `documents.storage_disk` and `documents.storage_path`.

## Artifacts

Artifact rows can store JSON, text, or file metadata. File metadata is normalized to include:

```json
{
  "disk": "local",
  "path": "artifacts/report.txt",
  "storage_intent": "runtime_artifact"
}
```

The storage strategy validates relative paths and blocks traversal segments. It does not upload files or introduce cloud storage integration in this slice.

## Readiness

`App\Support\Storage\RuntimeStorageStrategy::report()` inspects storage configuration only. In production, public runtime storage is treated as unsafe unless `RUNTIME_STORAGE_DISALLOW_PUBLIC_IN_PRODUCTION=false` is set deliberately.
