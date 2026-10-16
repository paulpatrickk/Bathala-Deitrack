<?php
// geocode_lib.php
// Shared helper for calling Nominatim from the server. Nominatim's usage
// policy requires a real identifying User-Agent, which browser fetch()
// cannot set — that's why the location picker now goes through these PHP
// proxies instead of calling nominatim.openstreetmap.org from the browser.

function deitrack_geocode_fetch($url) {
    $userAgent = 'DeiTrack-POS/1.0 (Bathala Tissue; contact: staffbul.pos@deitrack.com)';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $code >= 200 && $code < 300) return $body;
        return null;
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: $userAgent\r\nAccept: application/json\r\n",
                'timeout' => 8,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return $body !== false ? $body : null;
    }

    return null;
}