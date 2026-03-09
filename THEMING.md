# SkyOps — Theming & Customization Guide

A beginner-friendly guide to customizing colors, fonts, and appearance of your SkyOps module.

> **You only need to edit ONE file:**
> ```
> modules/SkyOps/Resources/views/partials/_styles.blade.php
> ```
> After editing, clear your view cache: delete all files in `storage/framework/views/` and visit `/update`.

---

## How It Works

SkyOps uses **CSS Variables** — simple named values at the top of the stylesheet that control the entire look. Change one variable and every button, card, badge, and text that uses it updates automatically.

Think of it like a paint bucket: instead of repainting every wall individually, you change the color in the bucket and everything that was painted from it changes at once.

---

## The Color Variables — Line by Line

Open `_styles.blade.php` and look at lines 5–27. Here is every variable explained:

### Accent Colors (Line 6–11)

These are the "brand colors" used for buttons, badges, status indicators, and highlights.

```css
--ap-cyan:    #0ea5e9;   /* Sky blue — used for smooth landing rate, info badges */
--ap-blue:    #3b82f6;   /* Primary blue — active tabs, buttons, pagination, hover effects */
--ap-violet:  #818cf8;   /* Violet — secondary accent, special badges */
--ap-green:   #22c55e;   /* Green — success states, health "active", revenue numbers */
--ap-amber:   #f59e0b;   /* Amber/Orange — warnings, health "inactive", landing rate icon */
--ap-red:     #ef4444;   /* Red — errors, health "dormant", expenses, live flight pulse */
```

**Example customizations:**
- Want orange buttons instead of blue? Change `--ap-blue: #f97316;`
- Want a teal accent? Change `--ap-cyan: #14b8a6;`
- Want gold warnings? Change `--ap-amber: #eab308;`

### Fonts (Line 12–14)

```css
--ap-font-head: 'Outfit', sans-serif;     /* Headlines, navigation tabs, buttons */
--ap-font-mono: 'JetBrains Mono', monospace; /* Numbers, flight times, landing rates, badges */
--ap-font-body: 'Inter', sans-serif;       /* Body text, descriptions, table content */
```

**To change fonts:**
1. Pick a Google Font from [fonts.google.com](https://fonts.google.com)
2. Update the `<link>` tag in `layouts/app.blade.php`
3. Change the variable here

**Example:** To use Roboto for body text:
```css
--ap-font-body: 'Roboto', sans-serif;
```

### Dark Mode Backgrounds (Line 16–26)

These control how SkyOps looks on **dark themes** (the default for most phpVMS themes).

```css
--ap-surface:    #1a1f2e;   /* Main background — cards, navigation bar, stat boxes */
--ap-border:     #2a3040;   /* Normal borders around cards and inputs */
--ap-border2:    #3a4055;   /* Stronger borders — hover states, emphasis */
--ap-card-bg:    #171c28;   /* Inner card background — slightly darker than surface */
--ap-text:       #e2e8f0;   /* Normal body text color */
--ap-text-head:  #ffffff;   /* Headlines and important numbers */
--ap-muted:      #cbd5e1;   /* Subdued text — labels, hints, inactive items */
--ap-select-bg:  #1e2536;   /* Dropdown and input field backgrounds */
--ap-tag-bg:     #1e2536;   /* Tag/chip backgrounds */
--ap-tag-color:  #e2e8f0;   /* Tag/chip text color */
--ap-divider:    #242a3a;   /* Horizontal dividers and separators */
```

**Example customizations:**
- Darker background? `--ap-surface: #111827; --ap-card-bg: #0f1320;`
- Lighter dark mode? `--ap-surface: #1e2940; --ap-card-bg: #1a2435;`
- Blue-tinted dark? `--ap-surface: #0f172a; --ap-card-bg: #0c1222;`

### Light Mode Backgrounds (Line 31–42)

These activate automatically when your phpVMS theme uses a light/white design.

```css
--ap-surface:    #ffffff;   /* Main background — pure white */
--ap-border:     #e2e8f0;   /* Light gray borders */
--ap-border2:    #cbd5e1;   /* Slightly darker borders for emphasis */
--ap-card-bg:    #f8fafc;   /* Very light gray card background */
--ap-text:       #1e293b;   /* Dark text for readability */
--ap-text-head:  #0f172a;   /* Near-black headlines */
--ap-muted:      #64748b;   /* Gray subdued text */
--ap-select-bg:  #f1f5f9;   /* Very light input backgrounds */
--ap-tag-bg:     #f1f5f9;   /* Light tag backgrounds */
--ap-tag-color:  #334155;   /* Dark tag text */
--ap-divider:    #e2e8f0;   /* Light dividers */
```

**Example customizations:**
- Warm light mode? `--ap-card-bg: #fffbf5; --ap-surface: #fffdf8;`
- Cool light mode? `--ap-card-bg: #f0f4ff; --ap-surface: #f8faff;`

---

## What Each Component Looks Like

Here is where each variable appears in the interface:

| Variable | Where you see it |
|---|---|
| `--ap-blue` | Active navigation tab, primary buttons, pagination dots, active period tab |
| `--ap-green` | "Active" health badge, revenue numbers, accepted PIREP status, smooth landing icon |
| `--ap-amber` | "Inactive" health badge, warning states, medium landing rate icon |
| `--ap-red` | "Dormant" health badge, expense numbers, rejected PIREPs, live flight pulse |
| `--ap-cyan` | Smooth landing rate color, info badges |
| `--ap-violet` | Special badges, secondary accents |
| `--ap-surface` | Card backgrounds, navigation bar, stat boxes, filter area |
| `--ap-card-bg` | Inner card areas (slightly different from surface for depth) |
| `--ap-border` | Every card edge, input field borders, table separators |
| `--ap-text` | All normal body text, table content |
| `--ap-text-head` | Page titles, card titles, big stat numbers |
| `--ap-muted` | Column headers, labels, filter hints, inactive tab text |
| `--ap-select-bg` | Dropdown menus, search inputs, text fields |
| `--ap-font-head` | Navigation tabs, buttons, card titles, filter labels |
| `--ap-font-mono` | Flight times, distances, landing rates, badge labels, stat numbers |
| `--ap-font-body` | Everything else — descriptions, table text, help content |

---

## Common Recipes

### Recipe 1: Match Your Airline Brand Color

If your VA brand color is `#e63946` (red):

```css
--ap-blue: #e63946;   /* Buttons, active tabs, pagination — now your brand red */
```

Everything that was blue is now your brand color. The green/amber/red status colors stay unchanged so health badges still make sense.

### Recipe 2: Fully Dark "OLED" Theme

```css
--ap-surface:    #000000;
--ap-border:     #1a1a1a;
--ap-border2:    #2a2a2a;
--ap-card-bg:    #0a0a0a;
--ap-select-bg:  #111111;
--ap-tag-bg:     #111111;
--ap-divider:    #1a1a1a;
```

### Recipe 3: Navy Blue Theme

```css
--ap-surface:    #0f172a;
--ap-border:     #1e3a5f;
--ap-border2:    #2d4a6f;
--ap-card-bg:    #0c1322;
--ap-select-bg:  #132040;
--ap-tag-bg:     #132040;
--ap-divider:    #1a2d50;
```

### Recipe 4: Warm/Amber Accent Instead of Blue

```css
--ap-blue:    #f59e0b;   /* Amber buttons and tabs */
--ap-cyan:    #fbbf24;   /* Gold info accent */
```

### Recipe 5: Switch to Solid Mode (opaque backgrounds)

Remove the `so-glass` class in `layouts/app.blade.php`:
```html
<!-- Glass (default): -->
<div class="so-wrap so-glass">

<!-- Solid (opaque): -->
<div class="so-wrap">
```

### Recipe 6: Change Only the Font

In `layouts/app.blade.php`, change the Google Fonts link:
```html
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
```

Then in `_styles.blade.php`:
```css
--ap-font-head: 'Poppins', sans-serif;
--ap-font-mono: 'Fira Code', monospace;
```

---

## Other Things You Can Customize

### Glass Mode (Transparent Cards)

SkyOps comes with two visual modes:

- **Glass** (default) — semi-transparent cards with blur effect, your theme background shines through
- **Solid** — opaque card backgrounds, best for themes with busy background images

Glass mode is enabled by default. If you have readability issues (e.g. a busy background image), switch to Solid:

**To switch to Solid mode:**

Open `modules/SkyOps/Resources/views/layouts/app.blade.php` and find:
```html
<div class="so-wrap so-glass">
```

Change it to:
```html
<div class="so-wrap">
```

Remove the `so-glass` class — that's all.

**When to keep Glass (default):**
- Your theme has a solid dark background color → Glass looks great
- Your theme has a gradient or subtle pattern → Glass looks elegant

**When to switch to Solid:**
- Your theme has a busy background image → Solid is more readable
- You want maximum contrast and readability → Solid is safer

### Card Border Radius (Line 58)

The roundness of cards. Default is `14px`.

```css
/* Rounded (default) */
.so-card { border-radius: 14px; }

/* Sharp corners */
.so-card { border-radius: 4px; }

/* Fully rounded */
.so-card { border-radius: 24px; }
```

### Navigation Tab Style (Line 53)

The active tab background:

```css
/* Default: solid blue */
.so-nav a.active { color: #fff; background: var(--ap-blue); }

/* Outline style instead */
.so-nav a.active { color: var(--ap-blue); background: transparent; border: 2px solid var(--ap-blue); }

/* Underline style */
.so-nav a.active { color: var(--ap-blue); background: transparent; border-bottom: 3px solid var(--ap-blue); border-radius: 0; }
```

### Stat Box Hover Effect (Line 171)

The lift-up effect when hovering over stat boxes:

```css
/* Default: subtle lift */
.so-stat-box:hover { transform: translateY(-2px); }

/* Disable hover effect */
.so-stat-box:hover { transform: none; }

/* Stronger lift with glow */
.so-stat-box:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(59,130,246,.15); }
```

### Footer Credit Line (Line 203–209)

The "crafted with ♥" footer at the bottom:

```css
/* Default: very subtle */
.so-footer { opacity: .45; }

/* More visible */
.so-footer { opacity: .7; }

/* Completely hidden (not recommended — license requires attribution) */
.so-footer { display: none; }    /* ⚠️ Check LICENSE before hiding */
```

---

## Step-by-Step: Making Your First Change

1. **Open** `modules/SkyOps/Resources/views/partials/_styles.blade.php` in a text editor

2. **Find** the variable you want to change (lines 5–27 for dark, 31–42 for light)

3. **Change** the hex color value. Use a color picker:
   - [Google Color Picker](https://g.co/kgs/YkmGq5) — just google "color picker"
   - [Coolors](https://coolors.co) — palette generator

4. **Save** the file

5. **Clear the cache:**
   - Delete all files in `your-phpvms/storage/framework/views/`
   - Visit `/update` in your browser

6. **Reload** the SkyOps page — your changes are live!

---

## Troubleshooting

| Problem | Solution |
|---|---|
| Changes don't appear | Delete `storage/framework/views/*` and visit `/update` |
| Colors look wrong | Make sure you use valid hex codes (e.g. `#3b82f6`, not `3b82f6`) |
| Dark/light mode not switching | SkyOps auto-detects your theme. Check if your theme sets `data-bs-theme="dark"` or `data-bs-theme="light"` on the `<html>` tag |
| Background is transparent | That's Glass mode (default). For solid backgrounds, remove `so-glass` from the `<div class="so-wrap so-glass">` in `layouts/app.blade.php` |
| Font not loading | Check that the Google Fonts `<link>` in `layouts/app.blade.php` matches your font name |

---

## Quick Reference Card

```
ACCENT COLORS              DARK BACKGROUNDS           LIGHT BACKGROUNDS
─────────────              ────────────────           ─────────────────
--ap-blue    → buttons     --ap-surface   → cards     --ap-surface   → cards
--ap-green   → success     --ap-card-bg   → inner     --ap-card-bg   → inner
--ap-amber   → warning     --ap-border    → edges     --ap-border    → edges
--ap-red     → danger      --ap-text      → text      --ap-text      → text
--ap-cyan    → info        --ap-text-head → titles    --ap-text-head → titles
--ap-violet  → special     --ap-muted     → subtle    --ap-muted     → subtle

FILE: modules/SkyOps/Resources/views/partials/_styles.blade.php
CLEAR CACHE AFTER EVERY CHANGE!
```

---

*SkyOps — crafted with ♥ in Germany by Thomas Kant*
