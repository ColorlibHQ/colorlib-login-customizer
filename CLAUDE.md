# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Colorlib Login Customizer is a WordPress plugin that customizes the WordPress login page via the WordPress Customizer interface. It provides real-time preview of login form styling and layout changes.

**Repository**: https://github.com/puikinsh/colorlib-login-customizer
**PHP**: 8.0+ (verified through 8.5) | **WordPress**: 6.0+ | **Tested up to**: 7.0

Current version is `2.1.1`. The version is declared in three places that must stay in sync on every release: the plugin header in `colorlib-login-customizer.php`, the `COLORLIB_LOGIN_CUSTOMIZER_VERSION` constant, and `readme.txt`'s `Stable tag`. `package.json`'s `version` should match too (it drives the Grunt build archive name). When shipping a change after a released version, add a NEW `= x.y.z =` heading at the top of the `readme.txt` changelog — do not edit already-released entries or `@since` docblock tags.

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
jshint ./assets/js/clc-customizer.js ./assets/js/clc-preview.js

# PHP coding standards check
composer lint
# or
./vendor/bin/phpcs
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
└── assets/
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

5. **Custom Controls** (`includes/lib/controls/`) - Extend `WP_Customize_Control`: color picker, toggle, range slider, template selector, button group, background, and column-width controls

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

- `clc_sanitize_color()` - Validates hex, rgb, rgba colors
- `clc_sanitize_dimension()` - Validates CSS dimensions (px, em, %, etc.)
- `clc_sanitize_css_value()` - Generic CSS value sanitization
- `clc_sanitize_url()` - URL validation
- `clc_sanitize_text()` - Single-line text
- `clc_sanitize_textarea()` - Multi-line with allowed HTML
- `clc_sanitize_css()` - Custom CSS with injection protection
- `clc_sanitize_image()` - Image URL validation
- `clc_sanitize_columns_width()` - Column width array validation

## CI/CD

Travis CI runs on all PRs:
- PHP syntax validation (8.0, 8.1, 8.2, 8.3, 8.4, 8.5)
- JSHint linting
- PHPCS with WordPress-Core ruleset + PHPCompatibility

## Compatibility (PHP 8.5.x / WordPress 7.x)

Audited 2026-06-19 — code is compatible with **PHP up to 8.5** and **WordPress up to 7.0**.

How it was verified (re-run any of these when touching PHP):

```bash
# 1. Syntax-compile every file under the target PHP (catches removed syntax)
find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' | xargs -n1 php -l

# 2. Authoritative compat sniff (pin testVersion explicitly)
./vendor/bin/phpcs -p --standard=PHPCompatibilityWP --runtime-set testVersion 8.5 \
  --extensions=php --ignore="*/vendor/*,*/node_modules/*,*minicolors*" .
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

- **jQuery minicolors** (`assets/js/jquery.minicolors.js`) - Color picker library, excluded from linting
- Compatibility fix for "All In One WP Security" plugin in main class

## Theme Compatibility

### Astra Theme Style Guide Overlay

**Issue**: Astra theme injects a "Style Guide" overlay (`.ast-style-guide-wrapper`) into the WordPress Customizer preview. When previewing the login page, this overlay appears and causes visual conflicts with the login form preview.

**Solution**: CSS rules in `get_base_css()` hide Astra-specific elements on the login page:

```css
.ast-style-guide-wrapper,
.ast-quick-tour-body,
.ast-close-tour,
.ast-tour-inner-wrap {
    display: none !important;
}
```

**Location**: `includes/lib/class-colorlib-login-customizer-css-customization.php` in the `get_base_css()` method (around line 1232)

**Scope**: This CSS only applies on the WordPress login page (`wp-login.php`) because it's output via the `login_head` action hook. Astra's style guide continues to work normally on the front-end, admin, and main theme customizer.

**If issues arise**: If Astra changes their class names or if other themes have similar conflicts, update the CSS selectors in `get_base_css()`. The pattern is to hide any theme-injected overlay elements that don't belong on the login page.

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
