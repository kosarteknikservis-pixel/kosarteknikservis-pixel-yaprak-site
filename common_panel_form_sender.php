<?php
// Bu dosya, form başvurularını Common Panel'e gönderir.
// form-islem.php içinde başarılı INSERT sonrası çağrılır.

function sendFormToCommonPanel($formData, $settings) {
    if (!isset($settings['ayar_common_panel_status']) || $settings['ayar_common_panel_status'] != 1) {
        return false;
    }

    $panelUrl = isset($settings['ayar_common_panel_url']) ? rtrim($settings['ayar_common_panel_url'], '/') : '';
    $panelKey = isset($settings['ayar_common_panel_key']) ? $settings['ayar_common_panel_key'] : '123456';

    if (empty($panelUrl)) return false;

    // Prepare Multipart Data
    $payload = [
        'api_key'     => $panelKey,
        'site_origin' => $formData['site_origin'] ?? '',
        'form_name'   => $formData['form_name']   ?? '',
        'fields'      => json_encode($formData['fields'] ?? [], JSON_UNESCAPED_UNICODE)
    ];

    // Add Files if any
    if (!empty($formData['files']) && is_array($formData['files'])) {
        foreach ($formData['files'] as $key => $filePath) {
            if (file_exists($filePath)) {
                $payload[$key] = new CURLFile($filePath);
            }
        }
    }

    session_write_close();

    $ch = curl_init($panelUrl . '/api/receive_form.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // cURL handles multipart/form-data automatically for arrays
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Increased timeout for file transfers
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $response;
}
?>
