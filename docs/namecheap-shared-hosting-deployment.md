# Aza Akaunting on Namecheap shared hosting

## Target layout

Serve the public Aza website at `https://azakaunting.com/` and the Open Source POS application at `https://azakaunting.com/app/` on the same domain. This keeps the marketing site and app under one TLS certificate and one hostname.

The cPanel File Manager screenshot shows an `azakaunting.com` folder separate from `public_html`, which already contains another site's files. Verify that cPanel → Domains lists `/home/<cpanel-user>/azakaunting.com` as the document root, and leave `public_html` untouched. Keep the framework and application files outside the domain's web root. Use a layout like:

```text
/home/<cpanel-user>/aza-pos/                 GitHub checkout and private application root
    app/
    vendor/
    writable/
    .env                                      production-only settings
    composer.json / composer.lock

/home/<cpanel-user>/azakaunting.com/          azakaunting.com document root
    index.html                                Aza public site
    about.html
    faq.html
    feature-details.html
    assets/
    app/                                      only files from OSPOS public/
        index.php
        .htaccess
        css/ js/ images/ fonts/ uploads/ ...
```

Use cPanel's Domains page to verify the actual document root before deploying. The domain root receives the Aza pages and only OSPOS's public files. Never place the private `app/`, `vendor/`, `.env`, `writable/`, backups, or `.git/` inside the domain's document root.

The deployed `azakaunting.com/app/index.php` needs to load the private app's Paths file. From `/home/<cpanel-user>/azakaunting.com/app/index.php`, that path is:

```php
require FCPATH . '/../../aza-pos/app/Config/Paths.php';
```

Keep the rest of the OSPOS project root intact so `app/Config/Paths.php` can continue resolving `vendor/`, `writable/`, and `.env` relative to the private app root. Make this change in the deployment copy, not the local source front controller.

The public site's app buttons already point to `/app/`.

## Compatibility gate

Before uploading, check cPanel's PHP Selector and the hosting account's actual web and CLI PHP versions. This checkout's `composer.json` supports PHP 8.2.35–8.2.x, 8.3.36–8.3.x, or 8.4.27 and newer. So PHP 8.2 is compatible if the actual patch is at least 8.2.35; use PHP 8.4.27+ if available. Enable and verify `intl`, `gd`, `bcmath`, `openssl`, `mbstring`, `curl`, `xml`, and `mysqli`/mysqlnd. JSON is normally built into PHP.

The app requires MySQL 5.7+ or MariaDB 10.x. Confirm the cPanel-provided database version and that `localhost` is the database host for this account. Confirm rewrite support and `.htaccess` behavior on the hosting server before pointing the live domain at it.

## GitHub deployment flow

1. Push this customized OSPOS checkout to GitHub so it includes the Aza pages in `aza akaunting_public website/`; do not replace it with an untouched upstream repository.
2. In cPanel, clone the GitHub repository to `/home/<cpanel-user>/aza-pos/`, outside the domain document root. Never clone the entire repository into the `azakaunting.com` web root.
3. Install production Composer dependencies in that private checkout (`composer install --no-dev --optimize-autoloader`) using a compatible PHP CLI. `vendor/` is ignored by this repository, so it must be installed on the host or otherwise provisioned privately.
4. Build the frontend resources with Node 22+ (`npm ci` then `npm run build`). Namecheap lists Node 22/24 on shared servers. Do not commit or upload `node_modules/`.
5. Deploy only the four Aza HTML pages and their `assets/` folder to the domain root; deploy OSPOS's `public/` files under its `app/` subfolder. Use cPanel Git deployment with a top-level `.cpanel.yml` if the Git Version Control feature is available, or a controlled deployment command from cPanel Terminal. The deployment must use the adjusted front controller path above.
6. Exclude `.env`, `.env.lock`, local logs, local database files, and uploaded user data from GitHub. Keep production source and `writable/` private. Create a production `.env` on the host with `CI_ENVIRONMENT=production`, `app.allowedHostnames='azakaunting.com,www.azakaunting.com'`, database credentials, and the HTTPS site URL.
7. Back up any existing database and user-uploaded files before changing the live installation. If this is a fresh deployment, use a new database rather than importing local development data.

## cPanel setup and release sequence

1. Confirm cPanel → Domains lists `/home/<cpanel-user>/azakaunting.com` as the domain root. The existing `public_html` is for another site and should not be overwritten.
2. In cPanel, create a dedicated MySQL/MariaDB database and user, attach the user to that database, and grant the privileges needed for OSPOS migrations. Save credentials in the production `.env` only.
3. Clone the GitHub repository to `/home/<cpanel-user>/aza-pos/`. Deploy only Aza's public pages and assets to `/home/<cpanel-user>/azakaunting.com/`; deploy OSPOS `public/` contents to `/home/<cpanel-user>/azakaunting.com/app/`.
4. Set writable directories to the least permissive owner/group permissions that work for PHP. Keep `writable/` private and writable; never use `777` as a workaround.
5. Configure `.htaccess` for the `/app/` subdirectory, then enable HTTPS with the account's cPanel SSL tooling. Set the production hostname allowlist to the exact canonical hostnames used by the site.
6. Open the app once over HTTPS so first-install migrations can create the schema. Sign in with the installer credentials documented by OSPOS and immediately change the default administrator password. For a migration of an existing store, restore the matching production database and uploaded files instead of starting fresh.
7. Verify login/logout, POS checkout, image uploads, receipts, reports, multi-store access, and the Aza marketing site's navigation and app buttons. Review server/PHP logs after each check.
8. Keep a tested backup of the database, private app root, `.env`, and uploaded images. Establish a regular backup schedule before day-to-day use.

## Shared-hosting fit and limits

Namecheap shared hosting supports cPanel PHP selection, databases, Git, Node, and SSH/Terminal. SSH is disabled by default and must be enabled before using Git/Composer from the command line. Check whether Git Version Control is available in this cPanel account before relying on `.cpanel.yml` deployment.

This is a reasonable first deployment for a small team, but a POS is business-critical and concurrent checkout/reporting can be sensitive to shared CPU, process, and database limits. Before relying on it for multiple busy stores, run a staging pilot during normal peak usage. Move to a VPS or managed server if the shared account becomes slow, hits resource limits, or cannot provide the required PHP build/extensions.

## Information needed before the live cutover

- Confirm cPanel → Domains shows `/home/<cpanel-user>/azakaunting.com` as the document root.
- The Namecheap shared plan name and the PHP version/extensions shown in cPanel.
- Whether this is a fresh database or a migration of an existing live store and its uploaded images.
- A backup window and the person who will perform/approve the domain DNS change.

Never send cPanel passwords, database passwords, private keys, or `.env` contents in chat. Use cPanel/SFTP directly for credentials and deployment.
