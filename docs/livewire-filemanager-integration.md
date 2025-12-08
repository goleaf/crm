# Livewire File Manager Integration

## Overview
We have integrated `livewire-filemanager/filemanager` into Filament v4.3+ as a custom page. This provides a robust interface for managing files and folders, supporting drag-and-drop uploads, folder management, and media previews.

## Architecture
- **Package**: `livewire-filemanager/filemanager`
- **Filament Page**: `App\Filament\Pages\FileManager`
- **View**: `resources/views/filament/pages/file-manager.blade.php`
- **Config**: `config/livewire-filemanager.php`

## Usage
Navigate to **System > File Manager** in the Filament admin panel.

## Configuration
The configuration file is located at `config/livewire-filemanager.php`.
- **Disk**: By default, it uses the `public` disk.
- **ACL**: Access control lists can be enabled to restrict access to folders.

## Assets
We utilize the application's main Tailwind v4 build process. The vendor views are explicitly added to the `@source` directive in `resources/css/filament/admin/theme.css` to ensure all classes are generated.
**Note**: We purposely do NOT use the `@filemanagerStyles` directive as it injects a CDN link to Tailwind, which is not suitable for our production environment.

## Troubleshooting
- **Missing Styles**: If the file manager looks unstyled, ensure `npm run build` (or `npm run dev`) is running and that the vendor path in `theme.css` is correct.
- **Upload Errors**: Check the `storage/logs/laravel.log` and ensure the `public` disk is correctly configured and writable.
