# On This Day

A WordPress plugin that shows historical events which happened on today's date — the top 15 (configurable) events, pulled from Wikipedia's "On this day" API, sorted oldest to newest. Use it as a shortcode on any page/post, or drop it in as a widget.

## Features

- **Shortcode:** `[on_this_day]` — add it to any page or post.
- **Widget:** "On This Day" widget under Appearance > Widgets, backed by the same renderer as the shortcode.
- **Data source:** Wikipedia's public `onthisday/events` REST API, queried for the current month/day.
- **Random selection, sorted by year:** the API returns a full day's pool of events (often 40+); the plugin randomly picks the configured number from that pool, then displays them sorted by year, oldest to newest.
- **Daily caching:** the day's selection is cached until local midnight, so every visitor sees the same list and the plugin isn't hitting the Wikipedia API on every page load.
- **Admin setting:** Settings > On This Day lets you set the default number of events shown (1-100, default 15). A shortcode's `count` attribute overrides this per instance.

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

### After activating

- Optionally visit **Settings > On This Day** to change the default number of events shown (default 15).
- Create a Page, add the `[on_this_day]` shortcode to its content, and publish — the page will always reflect the current day automatically.
