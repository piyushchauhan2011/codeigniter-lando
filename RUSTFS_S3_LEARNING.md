# RustFS S3 Learning Notes

This project includes a contained object-storage demo for the job portal. Employers can upload private job assets to RustFS, store metadata in MySQL/SQLite, and request short-lived signed download URLs.

## What RustFS Provides

RustFS is an S3-compatible object store. The app talks to it with the AWS S3 SDK, but the storage service runs locally in Lando.

- Bucket: a top-level namespace, like `job-portal-assets`.
- Object key: the path-like identifier inside a bucket, like `job-assets/1/spec.pdf`.
- Metadata row: the app database stores ownership and display details; RustFS stores the bytes.
- Signed URL: a temporary bearer URL for downloading a private object without making the bucket public.

## Lando Services

Start or rebuild Lando after changing `.lando.yml`:

```bash
lando rebuild -y
lando start
```

RustFS endpoints:

- Console: `http://rustfs.my-first-lamp-app.lndo.site:8000`
- S3 API for browser/signed URLs: `http://rustfs-api.my-first-lamp-app.lndo.site:8000`
- S3 API from the appserver container: `http://rustfs:9000`

Default demo credentials are `rustfsadmin` / `rustfsadmin`.

## App Configuration

The defaults live in `app/Config/ObjectStorage.php`, and the sample `.env` keys are documented in `env`:

```ini
objectStorage.endpoint = 'http://rustfs:9000'
objectStorage.publicEndpoint = 'http://rustfs-api.my-first-lamp-app.lndo.site:8000'
objectStorage.region = 'us-east-1'
objectStorage.accessKey = 'rustfsadmin'
objectStorage.secretKey = 'rustfsadmin'
objectStorage.bucket = 'job-portal-assets'
objectStorage.signedUrlTtl = 300
objectStorage.usePathStyleEndpoint = true
```

The app uses the internal endpoint for upload/delete operations and the public endpoint when signing URLs. S3 signatures include the host, so signed links need to be generated for the hostname the browser will use.

## Demo Flow

1. Run migrations and seed the portal.
2. Sign in as `employer@example.test` / `password123`.
3. Open the employer dashboard.
4. Click `Assets` for a job.
5. Upload a small PDF, image, document, or text file.
6. Click `Signed download`.

The app checks that the signed-in employer owns the job before uploading, deleting, or issuing a signed URL. Seekers and guests are blocked by the existing route filters before they reach the asset controller.

## Real RustFS Smoke Check

Feature tests use a fake storage client, so normal tests do not need RustFS. To verify the live container manually:

```bash
lando php spark rustfs:smoke
```

That command creates the bucket if needed, uploads a tiny text object, generates a signed URL, deletes the object, and prints the result.

## Security Lessons

Signed URLs are bearer tokens. Anyone with the URL can fetch the object until the URL expires, so keep TTLs short and only issue links after app-level authorization passes.

Keep buckets private by default. The database row is where the app tracks who owns an object; the S3 object key alone should not be treated as proof of permission.
