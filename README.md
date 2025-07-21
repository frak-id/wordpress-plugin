# Frak WordPress Plugin

WordPress integration plugin for the Frak rewards and engagement platform.

## Structure

```
frak-integration.php      - Main plugin file
includes/                 - Core plugin classes
  ├── class-frak-plugin.php       - Main plugin controller
  ├── class-frak-frontend.php     - Frontend functionality
  └── class-frak-woocommerce.php  - WooCommerce integration
admin/                    - Admin functionality
  ├── class-frak-admin.php        - Admin settings
  └── views/              - Admin UI templates
```

## Features

- **SDK Integration**: Automatically injects Frak SDK into your WordPress site
- **Floating Wallet Button**: Optional floating button for user engagement
- **WooCommerce Support**: Track purchases and reward users (optional)
- **Customizable Configuration**: Full control over SDK settings via admin panel

## Building for Distribution

To create a distributable plugin package:

```bash
# Make build script executable (first time only)
chmod +x build.sh

# Run the build script
./build.sh
```

This will create a zip file in the `dist/` directory ready for distribution.

## Installation

1. Upload the plugin files to `/wp-content/plugins/frak-integration/`
2. Activate the plugin through the WordPress admin
3. Configure settings under Settings > Frak

## Development

The plugin follows WordPress coding standards and best practices:
- Object-oriented architecture
- Proper hook usage
- Sanitization and escaping
- Internationalization ready
