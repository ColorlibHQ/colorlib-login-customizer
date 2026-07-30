# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Colorlib Login Customizer is a WordPress plugin that customizes the WordPress login page via the WordPress Customizer interface. It provides real-time preview of login form styling and layout changes.

**Repository**: https://github.com/puikinsh/colorlib-login-customizer
**PHP**: 8.0+ (verified through 8.5) | **WordPress**: 6.0+ | **Tested up to**: 7.0

Current version is `2.3.0`. The version is declared in three places that must stay in sync on every release: the plugin header in `colorlib-login-customizer.php`, the `COLORLIB_LOGIN_CUSTOMIZER_VERSION` constant, and `readme.txt`'s `Stable tag`. `package.json`'s `version` should match too (it drives the Grunt build archive name). When shipping a change after a released version, add a NEW `= x.y.z =` heading at the top of the `readme.txt` changelog — do not edit already-released entries or `@since` docblock tags.

## Build Commands

```bash
# Install dependencies
npm install
composer install

# Build release archive (runs i18n → clean → copy → compress)
grunt build-archive

# Generate POT file and validate text domains
grunt i18n

# Lint JavaScript
npx jshint ./assets/js/clc-customizer.js ./assets/js/clc-preview.js

# PHP coding standards check
composer lint

# PHP 8.0+ compatibility sniff
composer compat

# Unit tests (no WordPress install required)
composer test
```

## Architecture

### Plugin Structure

The plugin uses WordPress Customizer API with a singleton pattern:

```
colorlib-login-customizer.php          # Entry point, PHP version check, defines constants, bootstrap
├── includes/
│   ├── class-colorlib-login-customizer.php                       # Main singleton class
│   ├── class-colorlib-login-customizer-autoloader.php            # PSR-4 autoloader
│   ├── class-colorlib-login-customizer-sanitization.php          # Input sanitization functions
│   ├── class-colorlib-login-customizer-backwards-compatibility.php # Migrates legacy option keys
│   ├── class-colorlib-login-customizer-review.php                # CLC_Review: admin "rate us" notice
│   ├── login-template.php                                        # Custom login page template
│   └── lib/
│       ├── class-colorlib-login-customizer-customizer.php        # Registers customizer panels/sections
│       ├── class-colorlib-login-customizer-css-customization.php # Generates dynamic CSS
│       ├── class-colorlib-login-customizer-settings.php          # Admin menu page + settings link (singleton)
│       └── controls/                                             # Custom WP_Customize_Control classes
│                                                                  # (template, button-group, column-width only)
└── assets/
    ├── css/clc-login.css       # Static base styles for the login page (cacheable)
    ├── js/clc-customizer.js    # Customizer control interactions (jQuery)
    ├── js/clc-preview.js       # Live preview logic
    └── css/                    # Customizer UI styles
```

### Bootstrap & Initialization Flow

`colorlib-login-customizer.php` requires the autoloader, backwards-compatibility, and sanitization files eagerly (not autoloaded), then calls `colorlib_login_customizer()`. That function returns the `Colorlib_Login_Customizer::instance()` singleton and lazily attaches the `Colorlib_Login_Customizer_Settings` singleton to its `->settings` property. `CLC_Review` is loaded separately on `admin_init` via `clc_check_for_review()`. Most plugin behavior is wired up in the main class constructor (`customize_register`, `template_include`, `init` hooks). The settings, review, and backwards-compat classes each enforce their own singleton (`__clone`/`__wakeup` forbidden).

### Key Components

1. **Colorlib_Login_Customizer** (`includes/class-colorlib-login-customizer.php`) - Main singleton that initializes the plugin, manages hooks, and provides default settings

2. **Colorlib_Login_Customizer_Customizer** (`includes/lib/class-colorlib-login-customizer-customizer.php`) - Registers all customizer sections and ~50 settings for templates, layout, logo, background, form styling, buttons, and links

3. **Colorlib_Login_Customizer_CSS_Customization** (`includes/lib/class-colorlib-login-customizer-css-customization.php`) - Generates complete CSS from settings, includes CSS sanitization

4. **Sanitization Functions** (`includes/class-colorlib-login-customizer-sanitization.php`) - Helper functions for validating colors, dimensions, URLs, and CSS

5. **Custom Controls** (`includes/lib/controls/`) - Extend `WP_Customize_Control`: template selector, button group, and column-width. Colors, toggles and range sliders use core controls (`WP_Customize_Color_Control`, `checkbox`, `range`); the bespoke versions were removed in 2.3.0

6. **Colorlib_Login_Customizer_Settings** (`includes/lib/class-colorlib-login-customizer-settings.php`) - Singleton that adds the admin menu item and the "Settings" action link on the plugins screen; attached as `colorlib_login_customizer()->settings`

7. **CLC_Backwards_Compatibility** (`includes/class-colorlib-login-customizer-backwards-compatibility.php`) - Migrates old saved option keys on `admin_init` and exposes the `clc_backwards_compatibility_front` filter so legacy logo settings keep working. Touch this when renaming or restructuring saved settings.

8. **CLC_Review** (`includes/class-colorlib-login-customizer-review.php`) - Time-delayed admin notice asking for a WordPress.org review, dismissed/handled via the `clc_epsilon_review` AJAX action

### Preview Mode

The plugin detects preview mode via `is_customize_preview()` combined with:
- Query var `?colorlib-login-customizer-customization=true`
- User logged in check
- Capability check for `edit_theme_options`

## Code Standards

- **PHP**: 8.0+ with strict types, WordPress-Core coding standards via PHPCS
- **JavaScript**: WordPress preset, single quotes, jQuery/WP globals allowed
- **Text Domain**: `colorlib-login-customizer` for all translatable strings
- **Security**: All output escaped, all input sanitized, capability checks enforced

### Sanitization Functions Available

All of these accept `mixed` and normalise internally — they are Customizer
`sanitize_callback`s, so under `strict_types` a `string` hint would raise a
TypeError on a null setting.

- `clc_sanitize_color()` - hex (3/4/6/8), rgb/rgba and hsl/hsla in both legacy-comma and modern space syntax, named colours, `var()`, single-level `color-mix()`
- `clc_sanitize_dimension()` - numbers, any modern length unit (incl. `dvh`/`svh`/`lvh`/`ch`), sizing keywords, `calc()`/`clamp()`/`min()`/`max()`/`var()`
- `clc_sanitize_css_value()` - Generic CSS value sanitization
- `clc_sanitize_url()` - URL validation (http, https, mailto, tel)
- `clc_sanitize_text()` - Single-line text
- `clc_sanitize_textarea()` - Multi-line with allowed HTML
- `clc_sanitize_css()` - Custom CSS with injection protection
- `clc_sanitize_image()` - Image URL validation
- `clc_sanitize_columns_width()` - Column width array validation

Supporting helpers: `clc_stringify()`, `clc_css_has_structural_chars()`,
`clc_css_has_unsafe_function()`, `clc_css_parens_balanced()`,
`clc_css_strip_dangerous_tokens()`, `clc_escape_css_value()`.

**Why the token stripper loops.** `clc_css_strip_dangerous_tokens()` repeats
until the value stops changing. A single pass is exploitable: deleting the
inner match of a nested payload rejoins the outer halves into a working token
(`java` + `javascript:` + `script:` → `javascript:`). Each pass can only
shorten the string, so it always terminates. Never replace this with a single
`preg_replace()` — `tests/SanitizationTest.php` has a regression case for every
token.

**Escaping CSS on output.** Use `clc_escape_css_value()`, never `esc_attr()`.
HTML entities are not decoded inside a `<style>` element, so `esc_attr()`
blocks nothing there while corrupting legitimate values (a quoted font stack
became `&#039;Helvetica Neue&#039;`).

### gettext Filter Escaping

`Colorlib_Login_Customizer_CSS_Customization` replaces core login strings via
`gettext`. Whether a filter escapes depends on how **core** prints that string:

| Core output | Example strings | Filter must return |
|---|---|---|
| `_e()` / raw echo | Username label, Password, Registration confirmation, Lost your password?, Log in / Register links | escaped (`esc_html()`) |
| `esc_attr_e()` | Log In, Register, Get New Password (button values) | raw — core escapes it |
| `esc_html_e()` | Remember Me | raw — core escapes it |

Escaping a value core already escapes double-encodes it, so `Tom & Jerry's`
renders as `Tom &amp; Jerry&#039;s`. Check `wp-login.php` before changing one.
`includes/login-template.php` mirrors the same contexts for the Customizer
preview.

## Testing

PHPUnit runs without a WordPress install: `tests/bootstrap.php` and
`tests/wp-stubs.php` provide a hook registry, an option store and the handful
of core helpers the plugin touches.

```bash
composer test              # or ./vendor/bin/phpunit
```

- `tests/SanitizationTest.php` — the sanitization layer, including a
  regression case for every nested-token bypass fixed in 2.3.0.
- `tests/CssOutputTest.php` — drives the real classes: hook scoping (the
  privacy-policy filter must not be registered outside the login page),
  the printed stylesheet, and gettext escaping.

The stubs are deliberately minimal. For anything that needs real WordPress
behaviour, smoke test against the Local WP site instead.

## CI/CD

GitHub Actions (`.github/workflows/ci.yml`) runs on pushes to `master` and on all PRs:
- **php** job — matrix PHP 8.0–8.5: `php -l` over every file, then PHPUnit
- **standards** job — PHPCS (WordPress ruleset) and PHPCompatibilityWP at `testVersion 8.0-`
- **javascript** job — JSHint

Travis was removed in 2.3.0.

## Compatibility (PHP 8.5.x / WordPress 7.x)

Audited 2026-06-19 — code is compatible with **PHP up to 8.5** and **WordPress up to 7.0**.

How it was verified (re-run any of these when touching PHP):

```bash
# 1. Syntax-compile every file under the target PHP (catches removed syntax)
find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' | xargs -n1 php -l

# 2. Authoritative compat sniff (pin testVersion explicitly)
./vendor/bin/phpcs -p --standard=PHPCompatibilityWP --runtime-set testVersion 8.5 \
  --extensions=php --ignore="*/vendor/*,*/node_modules/*" .
```

Findings — all clean:
- No implicit-nullable params (8.4), dynamic properties (8.2), `${}` interpolation (8.2), removed casts/functions, or `trigger_error(E_USER_ERROR)` (8.5).
- Every plugin class declares all properties it assigns; custom controls inherit theirs from `WP_Customize_Control`. No `#[\AllowDynamicProperties]` is needed.
- WordPress side uses only long-stable APIs (Customizer, `login_head`, `template_include`, `wp_ajax_*`). `load_plugin_textdomain` is correctly hooked on `init` (priority 0), so it won't trip the WP 6.7+ just-in-time translation warning. The Customizer is de-emphasized but **not** removed in WP 7.x.

Caveats — important when trusting the green result:
- The pinned `phpcompatibility/php-compatibility` is **9.3.5**, whose sniff data only reaches ~PHP 8.0. So PHPCS confirms the 8.0 baseline; the 8.1–8.5 deprecation coverage above comes from manual scanning + an actual `php -l` under a real PHP 8.5 build. Don't read a clean PHPCS run as proof for 8.5 on its own.
- The one class of issue neither static check catches is *passing `null` to non-nullable internal params* (deprecated since 8.1). Heavy input sanitization mitigates it, but final sign-off needs a live smoke test under PHP 8.5 + WP 7.x with `WP_DEBUG = true`.
- `Tested up to: 7.0` is set in the plugin header and `readme.txt`; WordPress 7.0 is released, so this is valid to publish. Keep this value at or below the current stable WP release — WordPress.org rejects anything higher.

## Third-Party Libraries

- None. The jQuery minicolors color picker was removed in 2.3.0 when the custom colour control was replaced by core's `WP_Customize_Color_Control`.
- Compatibility fix for "All In One WP Security" plugin in main class

## Theme Compatibility

### Astra Theme Style Guide Overlay

**Issue**: Astra theme injects a "Style Guide" overlay (`.ast-style-guide-wrapper`) into the WordPress Customizer preview. When previewing the login page, this overlay appears and causes visual conflicts with the login form preview.

**Solution**: CSS rules in `assets/css/clc-login.css` hide Astra-specific elements on the login page:

```css
.ast-style-guide-wrapper,
.ast-quick-tour-body,
.ast-close-tour,
.ast-tour-inner-wrap {
    display: none !important;
}
```

**Location**: `assets/css/clc-login.css` (top of the file). Before 2.3.0 this lived in a `get_base_css()` method and was inlined on every request; it is now a cacheable stylesheet enqueued on `login_enqueue_scripts`.

**Scope**: This CSS only applies on the WordPress login page (`wp-login.php`) because it's output via the `login_head` action hook. Astra's style guide continues to work normally on the front-end, admin, and main theme customizer.

**If issues arise**: If Astra changes their class names or if other themes have similar conflicts, update the selectors in `assets/css/clc-login.css`. The pattern is to hide any theme-injected overlay elements that don't belong on the login page.

## Security Considerations

All user input must be:
1. Sanitized on save (use sanitization callbacks in customizer settings)
2. Escaped on output (use `esc_html()`, `esc_attr()`, `esc_url()`, etc.)
3. Validated against allowed values for selects/radios

CSS injection is prevented via `sanitize_css()` method which removes:
- JavaScript expressions
- Behavior/binding directives
- @import statements
- Data URLs in url() functions
