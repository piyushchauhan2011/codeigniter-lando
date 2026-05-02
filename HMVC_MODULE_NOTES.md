# HMVC and Modules Learning Notes

This project now has a small CodeIgniter 4 module in `featured_jobs/`, beside the main `app/` directory:

```text
app/
featured_jobs/
public/
system/
tests/
writable/
```

## What CodeIgniter 4 Means by Modules

CodeIgniter 4 modules are built from PSR-4 namespaces and auto-discovery. The `FeaturedJobs` namespace is registered in `app/Config/Autoload.php`:

```php
'FeaturedJobs' => ROOTPATH . 'featured_jobs',
```

Because `app/Config/Modules.php` already enables discovery for `routes`, CodeIgniter can find `featured_jobs/Config/Routes.php` without adding that route file manually to `app/Config/Routes.php`.

## Module Pieces

- `featured_jobs/Config/Routes.php` registers `/learning/modules/featured-jobs`.
- `featured_jobs/Controllers/FeaturedJobs.php` handles the full learning page.
- `featured_jobs/Models/FeaturedJobModel.php` reads from the existing job board tables.
- `featured_jobs/Cells/FeaturedJobsCell.php` renders a reusable module fragment.
- `featured_jobs/Views/index.php` is a namespaced module view.
- `featured_jobs/Language/en/FeaturedJobs.php` and `featured_jobs/Language/fr/FeaturedJobs.php` provide module-owned language strings.

## HMVC-Style Composition

Classic HMVC often means one controller invokes another module-like controller. In CodeIgniter 4, a cleaner learning-friendly version is to compose the page with view cells:

```php
<?= view_cell(\FeaturedJobs\Cells\FeaturedJobsCell::class, ['limit' => 3]) ?>
```

The host page lives in `app/Views/portal/jobs/index.php`, but the cell class, database query, and fragment view live in `featured_jobs/`. That keeps the feature modular while still letting the main job board reuse it.

## Try It

With the Lando site running, open:

- `/jobs` to see the module cell embedded in the existing job board.
- `/learning/modules/featured-jobs` to see the module-owned page.

If routes are cached in your environment, clear them before testing:

```bash
php spark cache:clear
```
