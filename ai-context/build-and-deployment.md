# Build, Local Dev & Deployment

## No build step

There is no `package.json`, no bundler, no compilation. `style.css` and `assets/js/script.js` are the literal files served (via `wp_enqueue_style`/`wp_enqueue_script` with `filemtime()`-based cache-busting versions — functions.php:180-194). Editing them is a direct edit-and-refresh workflow.

## Local development — Docker Compose

[docker-compose.yml](../docker-compose.yml) (git-ignored — present locally but not committed; listed in `.gitignore`) spins up:

- `db` — `mysql:8.0`, database/user/password all `wordpress` (root password `rootpassword`) — dev-only credentials, not used in production.
- `wordpress` — `wordpress:latest` image, port `8080:80`, `WORDPRESS_DEBUG: "1"`.
  - Mounts the **entire repo root** into `wp-content/themes/sufo` — meaning this repo *is* the theme folder, dropped straight into a stock WordPress install for local testing.
  - Mounts [uploads.ini](../uploads.ini) to `/usr/local/etc/php/conf.d/uploads.ini` to raise PHP's `upload_max_filesize`/`post_max_size`/`memory_limit` to 512M in the container — the Docker-side equivalent of the `.htaccess` marker injection functions.php does for shared hosting (see [theme-bootstrap-and-hooks.md](theme-bootstrap-and-hooks.md)).

To run locally: `docker compose up`, then visit `http://localhost:8080` and complete the standard WP install wizard, then activate the `sufo` theme.

## Deployment — FTP on push to `main`

[.github/workflows/deploy.yml](../.github/workflows/deploy.yml):

```yaml
on:
  push:
    branches: [main]
jobs:
  deploy:
    steps:
      - uses: actions/checkout@v4
      - uses: SamKirkland/FTP-Deploy-Action@v4.3.4
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          server-dir: /objects.suf.studio/wp-content/themes/sufo/
```

**Every push to `main` deploys straight to production** — there is no staging environment, no build/test gate, no manual approval step in this workflow. `FTP_SERVER`/`FTP_USERNAME`/`FTP_PASSWORD` are GitHub repo secrets. Treat `main` as the live branch: anything merged there is live on `objects.suf.studio` shortly after.

[.ftp-deploy-sync-state.json](../.ftp-deploy-sync-state.json) is the FTP-Deploy-Action's own sync-state cache (git-ignored) — deleting it forces a full resync on the next deploy, which the action's own embedded description warns is slow. Don't delete it casually.

## Upload size limits (two independent mechanisms)

1. **Production/shared hosting**: `functions.php`'s `init` hook writes `php_value upload_max_filesize/post_max_size/memory_limit 512M` into the site's root `.htaccess` via `insert_with_markers()` on every request (functions.php:23-31) — a workaround for hosts where `php.ini` can't be edited directly but Apache+mod_php honors `.htaccess` `php_value` directives.
2. **Local Docker**: `uploads.ini` mounted into the PHP container's `conf.d`, same three values.

Both exist because the two environments need the limit raised through different mechanisms — don't remove either thinking it's redundant with the other.

## What's git-ignored

From `.gitignore`: `docker-compose.yml`, `.DS_Store`, `.ftp-deploy-sync-state.json`. Note `docker-compose.yml` itself is ignored but currently exists in the working tree (used for local dev, just not version-controlled) — if it's missing on a fresh clone, it needs to be recreated from this document's description above, or the previous version reconstructed from `git log` history if it was ever committed.
