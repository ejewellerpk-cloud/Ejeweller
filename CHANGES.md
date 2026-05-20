# Project Custom Changes Log

This file contains a record of all custom modifications made to the Shopperzz project to ensure they can be re-applied after vendor updates.

## 1. Image Optimization (Shared Hosting Fix)
- **File**: `config/media-library.php`
- **Change**: Added conditional check for `ENABLE_IMAGE_OPTIMIZER` environment variable.
- **Environment Variable**: `ENABLE_IMAGE_OPTIMIZER=false` in `.env`.
- **Purpose**: Prevents `proc_open` errors on shared hosting by disabling image optimization binaries.

## 2. Product Details Image Slider
- **File**: `resources/js/components/frontend/product/ProductDetailsComponent.vue`
- **Changes**: 
    - Added `Pagination` module to Swiper.
    - Enabled pagination bullets for better mobile navigation.
    - Hidden thumbnails on small screens (`hidden sm:block`) to clean up UI.
    - Added `?v={{ time() }}` to favicon for cache busting.
- **Purpose**: Improves mobile and desktop user experience for products with multiple images.

## 3. Installer Bypass (License & Requirements)
- **File**: `app/Services/InstallerService.php`
- **Change**: Bypassed `licenseCodeChecker` to always return true.
- **File**: `config/installer.php`
- **Change**: Removed `Imagick` from requirements.
- **Purpose**: Allows installation without an active license key and on servers missing Imagick.

## 4. Robust Storage Link (Installer)
- **File**: `app/Services/InstallerService.php`
- **Change**: Added manual `@symlink` fallback in `finalSetup` method.
- **Purpose**: Ensures images work on shared hosting even if `Artisan::call('storage:link')` fails.

## 5. Favicon Cache Buster
- **File**: `resources/views/master.blade.php`
- **Change**: Added `?v={{ time() }}` and set `type="image/png"`.
- **Purpose**: Forces browsers to reload the favicon when it's updated.
