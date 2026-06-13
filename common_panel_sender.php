<?php
// Common Panel Sender Module
// This script sends order data to the central Common Panel via cURL

function sendOrderToCommonPanel($orderData, $settings) {
    
    // Check if Common Panel integration is enabled
    if (!isset($settings['ayar_common_panel_status']) || $settings['ayar_common_panel_status'] != 1) {
        return false;
    }

    $panelUrl = isset($settings['ayar_common_panel_url']) ? $settings['ayar_common_panel_url'] : '';
    $apiKey = isset($settings['ayar_common_panel_key']) ? $settings['ayar_common_panel_key'] : '';

    if (empty($panelUrl)) {
        return false;
    }

    // Add API Key to payload
    $orderData['api_key'] = $apiKey;

    // Encode data
    $payload = json_encode($orderData);

    // URL düzeltme: Eğer kullanıcı base URL girdiyse api pathini ekle
    $panelUrl = rtrim($panelUrl, '/');
    if (strpos($panelUrl, 'api/receive.php') === false) {
        $panelUrl .= '/api/receive.php';
    }

    // Performance Optimization
    session_write_close();

    // Initialize cURL
    $ch = curl_init($panelUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local dev
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Optional: Log errors if needed
    if ($httpCode != 200) {
        // file_put_contents('../common_panel_error.log', date('Y-m-d H:i:s') . " - Error: $error - Response: $response\n", FILE_APPEND);
    }

    return $httpCode == 200;
}
?>
