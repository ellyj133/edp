# Digital Products Directory

This directory stores uploaded digital/downloadable product files.

## Structure
```
/uploads/digital_products/
  ├── {vendor_id}/
  │   └── {product_id}/
  │       ├── digital_file_1.zip
  │       ├── digital_file_2.pdf
  │       └── ...
```

## Security
- Direct access is prevented via .htaccess
- Files are accessed through authenticated download.php endpoint
- Unique tokens required for each download

## Permissions
Ensure this directory is writable by the web server:
```bash
chmod 755 /path/to/uploads/digital_products
```
