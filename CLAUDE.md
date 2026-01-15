# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Colorlib Login Customizer is a WordPress plugin that customizes the WordPress login page via the WordPress Customizer interface. It provides real-time preview of login form styling and layout changes.

**Repository**: https://github.com/puikinsh/colorlib-login-customizer
**PHP**: 8.0+ | **WordPress**: 6.0+ | **Tested up to**: 6.9

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
colorlib-login-customizer.php          # Entry point, PHP version check, defines constants
├── includes/
│   ├── class-colorlib-login-customizer.php              # Main singleton class
│   ├── class-colorlib-login-customizer-autoloader.php   # PSR-4 autoloader
│   ├── class-colorlib-login-customizer-sanitization.php # Input sanitization functions
│   ├── login-template.php                               # Custom login page template
│   └── lib/
│       ├── class-colorlib-login-customizer-customizer.php       # Registers customizer panels/sections
│       ├── class-colorlib-login-customizer-css-customization.php # Generates dynamic CSS
│       └── controls/                                     # Custom WP_Customize_Control classes
└── assets/
    ├── js/clc-customizer.js    # Customizer control interactions (jQuery)
    ├── js/clc-preview.js       # Live preview logic
    └── css/                    # Customizer UI styles
```

### Key Components

1. **Colorlib_Login_Customizer** (`includes/class-colorlib-login-customizer.php`) - Main singleton that initializes the plugin, manages hooks, and provides default settings

2. **Colorlib_Login_Customizer_Customizer** (`includes/lib/class-colorlib-login-customizer-customizer.php`) - Registers all customizer sections and ~50 settings for templates, layout, logo, background, form styling, buttons, and links

3. **Colorlib_Login_Customizer_CSS_Customization** (`includes/lib/class-colorlib-login-customizer-css-customization.php`) - Generates complete CSS from settings, includes CSS sanitization

4. **Sanitization Functions** (`includes/class-colorlib-login-customizer-sanitization.php`) - Helper functions for validating colors, dimensions, URLs, and CSS

5. **Custom Controls** (`includes/lib/controls/`) - Extend `WP_Customize_Control` for color pickers, toggles, range sliders, template selectors, and button groups

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
- PHP syntax validation (8.0, 8.1, 8.2, 8.3)
- JSHint linting
- PHPCS with WordPress-Core ruleset + PHPCompatibility

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
