# On This Day

A WordPress plugin that shows historical events which happened on today's date, and people born on it — 15 of each by default (configurable), pulled from Wikipedia's "On this day" API, sorted oldest to newest. Use it as a shortcode on any page/post, or drop it in as a widget.

## Features

- **Shortcode:** `[on_this_day]` — add it to any page or post.
- **Widget:** "On This Day" widget under Appearance > Widgets, backed by the same renderer as the shortcode.
- **Data source:** Wikipedia's public `onthisday/events` REST API, queried for the current month/day.
- **Random selection, sorted by year:** the API returns a full day's pool of events (often 40+); the plugin randomly picks the configured number from that pool, then displays them sorted by year, oldest to newest.
- **Born on this day:** below the events, the same number of people born on today's date, picked at random from Wikipedia's births list (often 200+) and sorted by birth year. If the births list can't be loaded, the events still show.
- **Daily caching:** the day's selection is cached until local midnight, so every visitor sees the same list and the plugin isn't hitting the Wikipedia API on every page load.
- **Attribution footer:** the list ends with "WP On This Day news sourced from Wikipedia (CC BY-SA 4.0) -sysop-", where Wikipedia links to that day's Wikipedia article and CC BY-SA 4.0 links to the license; -sysop- is plain text. It appears wherever the shortcode or widget is used. Wikipedia's text is available under the [Creative Commons Attribution-ShareAlike 4.0 license](https://creativecommons.org/licenses/by-sa/4.0/), so the footer credits the source and links to that license; the Wikipedia article it links to lists its authors in the page history.
- **Admin setting:** Settings > On This Day lets you set the default number of events shown (1-100, default 15); the births section uses the same number. A shortcode's `count` attribute overrides this per instance.

## Usage

### Shortcode

Add to a page or post:

```
[on_this_day]
```

Override the count for a single instance:

```
[on_this_day count="10"]
```

### Widget

Add the "On This Day" widget to any widget area (Appearance > Widgets). It has an optional title and an optional count override; leave count blank to use the site-wide default.

### "On This Day" page

Create a normal WordPress Page, add the `[on_this_day]` shortcode to its content, and publish. The page will always reflect the current day's events automatically — no further setup needed.

## Settings

Go to **Settings > On This Day** to set the default number of events (1-100, default 15) shown by the shortcode and widget.

## Requirements

- WordPress 5.0+
- Outbound HTTPS access to `en.wikipedia.org` from the server

## Installation

### Option A: Upload the zip via wp-admin

1. Download the plugin zip (e.g. `WP-on-this-day-1.0.1.zip`).
2. In wp-admin, go to **Plugins > Add New Plugin > Upload Plugin**.
3. Choose the zip file and click **Install Now**.
4. Click **Activate Plugin**.
5. Add the `[on_this_day]` shortcode to a page, or add the "On This Day" widget to a widget area.

### Option B: Manual install (FTP / local dev)

1. Unzip the plugin so you have a `WP-on-this-day/` folder containing `on-this-day.php`, `includes/`, and `assets/`.
2. Copy that folder into your site's `wp-content/plugins/` directory (via FTP/SFTP, or directly on disk for a local dev site, e.g. Local by Flywheel: `app/public/wp-content/plugins/`).
3. In wp-admin, go to **Plugins** and click **Activate** under "On This Day".
4. Add the `[on_this_day]` shortcode to a page, or add the "On This Day" widget to a widget area.

### Installing from GitHub

The repository root *is* the plugin (no build step, no dependencies), so GitHub's **Code > Download ZIP** works for a quick install. GitHub names the folder `WP-On-this-day-main`, so WordPress installs it under that name; uploading a differently named zip later installs a second copy instead of updating the first. For a clean install that updates in place, build a release zip (below).

**A git clone carries files a web server should not serve.** Chiefly `.git`, which holds the project's entire history. (GitHub's Download ZIP is an export, so it has no `.git`; this applies to a working copy you cloned or copied from your own machine.) The plugin ships an `.htaccess` that refuses `.git`, `*.md`, logs and editor leftovers, and every directory has an `index.php` so nothing can be listed. `.htaccess` is read by Apache only, so on nginx add this to the server block:

```nginx
location ~ /wp-content/plugins/.*/\.(git|svn)(/|$) { deny all; }
location ~ /wp-content/plugins/.*\.(md|log|ya?ml|lock)$ { deny all; }
```

The surest fix is not to deploy `.git` at all: install a release zip built as below.

#### If you zip the plugin by hand

Building with `git archive` (below) is strongly preferred. If you make the zip manually from a clone or working copy, **do not include** any of these:

- `.git/` (the whole project history), `.svn/`, and `.github/`
- `.gitignore` and `.gitattributes`
- `docs/` (documentation for the repository, not part of the plugin)
- any `.zip` files, such as an earlier build sitting in the same folder
- editor and OS leftovers: `.idea/`, `.vscode/`, `.DS_Store`, `Thumbs.db`, `desktop.ini`
- logs, backups and scratch files: `*.log`, `*.bak`, `*.orig`, `*.rej`, `*.swp`
- anything of your own: notes, test files, credentials or `.env` files

What belongs in the zip is `on-this-day.php`, `uninstall.php`, `LICENSE`, `README.md`, `.htaccess`, the `index.php` files, and the `includes/` and `assets/` folders. Put them inside a single top-level folder named `WP-on-this-day`, so the zip contains `WP-on-this-day/on-this-day.php`. Check the finished zip by opening it before you upload. The plugin's Install health panel will warn you if a stray `.git`, `.gitignore` or `.gitattributes` slips through.

### Install health

The plugin checks itself, because documentation only helps people who read it. **Settings > On This Day** has an *Install health* panel, and an administrator sees a notice on the Plugins screen, if:

- the folder is not named `WP-on-this-day` (for example `WP-On-this-day-main` from Download ZIP), which would make the next proper install a second copy;
- a second copy of the plugin is installed in `wp-content/plugins`;
- repository-only files such as `.gitignore`, `.gitattributes` or `.github` are present, which a release zip never contains and usually means the zip was made by hand from a working copy;
- a `.git` directory is present. The plugin makes one request to your own site to test whether the server actually serves it (the result is cached for a day), and reports an error if it does.

### After activating

- Optionally visit **Settings > On This Day** to change the default number of events shown (default 15).
- Create a Page, add the `[on_this_day]` shortcode to its content, and publish. The page will always reflect the current day automatically.

## Building a release zip

There is nothing to compile. A release zip is the tracked files inside a `WP-on-this-day/` folder. From the repository root (requires Git):

```
git archive --format=zip --prefix=WP-on-this-day/ -o WP-on-this-day-<version>.zip HEAD
```

Use the version from the plugin header in `on-this-day.php`. This builds from the last commit (so uncommitted or stray files never ship), writes the forward-slash paths WordPress requires, and leaves out repo-only files (`.gitignore`, `.gitattributes`, `docs/`). Upload the result via **Plugins > Add New Plugin > Upload Plugin**; if a previous version is installed, choose **Replace current with uploaded**.

Avoid building the zip with PowerShell's `Compress-Archive` on Windows. It can write backslash path separators, which produce broken installs on Linux hosts.

## Documentation

- [Setting up WordPress for a BBS experience](docs/setup-bbs-on-wordpress.md): site setup, security, membership and menu guidance for running this and the other BBS-style plugins.

The `docs/` folder is for the repository only and is left out of release zips. If it ends up on a site anyway (a hand-made zip), its `index.php` stops it being listed and the plugin's `.htaccess` refuses `.md` files, so visitors still cannot read it.
