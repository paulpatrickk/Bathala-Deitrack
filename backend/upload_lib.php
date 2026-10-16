<?php
// ─── SAFE IMAGE UPLOADS ─────────────────────────────────
// Every upload in the system goes through save_uploaded_image().
// It checks what the file REALLY is (not what its name claims), picks the
// extension itself, and limits the size — so nobody can sneak a .php
// script onto the server disguised as a picture.

function save_uploaded_image(array $file, string $dir, string $prefix, int $maxBytes = 8 * 1024 * 1024): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [false, 'No file uploaded'];
    }
    if ($file['size'] > $maxBytes) {
        return [false, 'Image is too large (max ' . round($maxBytes / 1048576) . ' MB)'];
    }

    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return [false, 'Only JPG, PNG or WEBP images are allowed'];
    }

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = preg_replace('/[^A-Za-z0-9_\-]/', '', $prefix) . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/') . '/' . $filename)) {
        return [false, 'Failed to save file'];
    }
    return [true, $filename];
}

// Deletes an old image, but only a plain filename inside $dir (no "../" tricks).
function delete_uploaded_image(?string $filename, string $dir): void
{
    if (!$filename || basename($filename) !== $filename) return;
    $path = rtrim($dir, '/') . '/' . $filename;
    if (is_file($path)) @unlink($path);
}
