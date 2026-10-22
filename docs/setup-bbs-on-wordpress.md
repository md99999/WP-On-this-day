# Setting up WordPress for a BBS Experience!

**Author:** [sysop](https://maddogproductions.online)  
**Date:** September 21, 2026  

# 🖥️ WordPress BBS-Style Setup Guide

This guide walks through setting up a new WordPress site that behaves like a classic BBS: members-only areas, sysop controls, secure logins, and game plugins such as **Imperial Barons Online** and **Imperial Dominion Online**. and my other WordPress Plugins developed in the style of BBS Doors.

## 🔧 Pre-Setup Requirements

### 1. Install WordPress
Any modern hosting provider supports WordPress. A **.online** domain fits the BBS theme nicely, but any domain works.

### 2. Secure the Site Immediately
Security should be enabled *before* you open registration.

Recommended stack:

1. **JetPack** (brute-force protection + downtime monitoring)
2. **Wordfence** (firewall + login security)
3. **SSL certificate** (required)
4. **Really Simple SSL** (optional helper plugin)
5. **WP Activity Log** (recommended to monitor activity)

### 3. Create Your Sysop Email
Create:

`sysop@<your-domain>`

This will be used for:

1. WordPress admin notifications
2. Game plugin league features
3. Future sysop-level communications

## ⚙️ Initial Configuration Steps

### Step 1 — Install a Membership Plugin
A membership plugin gives you BBS-style control over who can see what.

**Recommended:** Ultimate Member (Optional)

Why:

1. Easy role management
2. Built-in content restriction
3. Custom login/registration pages
4. Works well with game plugins

All standard members should use the **Subscriber** role.

### Step 2 — Harden Login Security
Inside **Wordfence → Login Security**:

1. Enable **2FA for all users** (optional but recommended)
2. Add **Google reCAPTCHA v3** keys (mandatory; obtain keys from Google)
3. Enforce strong passwords
4. Protect admin accounts with 2FA and complex passwords (required)

This prevents bot registrations and brute-force attacks. Optionally, you can enable passkeys.

> **WordPress Security Note:** Be careful which plugins you install. Ensure they are current, up to date, and trusted. WordPress should be configured for automatic updates, and plugins should also auto-update whenever possible. Wordfence provides an excellent WAF (Web Application Firewall), but the first line of defense is what you install and how well you maintain your site.

### Step 3 — Choose and Configure a Theme
Themes are personal preference.

Options:

1. **Divi 4 / Divi 5** (high customization, BBS-style branding) or Any Theme you wish.
2. Add theme from **Dashboard → Appearance → Themes**

Customize:

1. Site name (your BBS name)
2. Tagline (your board's slogan)
3. Colors and layout to match your retro or modern style

### Step 4 — Decide Your Membership Model
You choose how open your board is.

#### Open Board
Anyone can register.

#### Closed Board
Only invited users can join.

All members should remain **Subscribers** unless you have special sysop or moderator roles.

### Step 5 — Install the WordPress Game Plugins
Install Maddog's plugins. **Follow the installation instructions in each repository README to build the ZIP file. It is not recommended to simply ZIP the repository because unwanted files may be included.**

**Source:** https://github.com/md99999

Available plugins:

1. **Imperial Barons Online**
2. **Imperial Dominion Online**
3. **WP BBS Slots**
4. **WP On This Day**
5. **Imperial Hackers Online**

These plugins:

1. Add multiple new pages
2. Create game dashboards
3. Add scoring pages
4. Add league pages

Because they generate many pages, you'll want to create a **custom menu** afterward.

Additional notes:

- Go to the WordPress Settings area and locate Imperial Barons Online.
- Review each settings page.
- Use the Dashboard option to create pages.
- Repeat the same process for Imperial Dominion Online.
- Add each game's home page to your navigation menu.
- Review the README.md files for widget and news teaser options.
- Imperial Dominion supports interleague BBS-to-BBS gameplay and requires participation from multiple WordPress BBS installations.
- Review plugin risks before installation.

Repository: https://github.com/md99999/

### Step 6 — Build Your Navigation Menu
Go to:

**Appearance → Menus**

Add:

1. Public pages (Home, About, Scoring, Rules)
2. Subscriber-only pages (Game dashboards, league pages, internal posts)

Use the shortcodes provided by the game dashboards to populate the pages.

Organize everything into a clean BBS-style structure.

### Step 7 — Test Everything Before Opening the Board
Test the following:

1. Game plugins
2. Scoring pages
3. Registration flow
4. Login flow
5. Subscriber-only pages
6. Public pages
7. Menu links
8. 2FA
9. reCAPTCHA
10. Email notifications
