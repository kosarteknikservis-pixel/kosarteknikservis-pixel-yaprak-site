<?php
include __DIR__ . '/xnull/controller/config.php';

if (!function_exists('form_islem_return_url')) {
    /**
     * Başarı/limit/hata sonrası yönlendirme: slug yoksa veya kayıt bulunamazsa ana sayfa.
     */
    function form_islem_return_url(PDO $db, $form_id) {
        $form_id = (int) $form_id;
        $base    = rtrim(SITE_URL, '/');
        if ($form_id < 1) {
            return $base . '/index.php';
        }
        $slugSor = $db->prepare('SELECT form_slug FROM formlar WHERE form_id = ? LIMIT 1');
        $slugSor->execute(array($form_id));
        $row = $slugSor->fetch(PDO::FETCH_ASSOC);
        $slug = ($row && isset($row['form_slug'])) ? trim((string) $row['form_slug']) : '';
        if ($slug === '') {
            return $base . '/index.php';
        }
        if (preg_match('/\.php$/i', $slug)) {
            return $base . '/' . $slug;
        }
        // Temiz URL rewrite gerektirmez — form.php her ortamda açılır
        return $base . '/form.php?slug=' . rawurlencode($slug);
    }
}

// Form Gönderim İşlemi
if (isset($_POST['dynamic_form_submit'])) {
    $form_id = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;

    if ($form_id < 1) {
        header('Location: ' . rtrim(SITE_URL, '/') . '/index.php?durum=noform');
        exit;
    }

    // 0. Güvenlik Kontrolü (Rate Limiting - 15 Dakika)
    $ip = isset($_SERVER['REMOTE_ADDR']) ? strip_tags((string) $_SERVER['REMOTE_ADDR']) : '';
    $zaman_siniri = date('Y-m-d H:i:s', strtotime('-15 minutes'));

    $limitSor = $db->prepare("SELECT form_id FROM form_basvurular WHERE basvuru_ip=:ip AND basvuru_tarih > :zaman");
    $limitSor->execute(array(
        'ip' => $ip,
        'zaman' => $zaman_siniri,
    ));

    if ($limitSor->rowCount() > 0) {
        $redirectUrl = form_islem_return_url($db, $form_id);
        $sep = (strpos($redirectUrl, '?') !== false) ? '&' : '?';
        header('Location: ' . $redirectUrl . $sep . 'durum=limit');
        exit;
    }

    // 1. Başvuruyu Kaydet
    $kaydet = $db->prepare("INSERT INTO form_basvurular SET
        form_id=:form_id,
        basvuru_ip=:ip,
        basvuru_tarih=NOW()
    ");
    $insert = $kaydet->execute(array(
        'form_id' => $form_id,
        'ip' => $ip,
    ));

    if ($insert) {
        $basvuru_id = $db->lastInsertId();

        // 2. Alanları İşle (PHP 8+: $_POST['alan'] yoksa foreach fatal veriyordu — beyaz ekran)
        if (!empty($_POST['alan']) && is_array($_POST['alan'])) {
            foreach ($_POST['alan'] as $alan_id => $deger) {
                if (is_array($deger)) {
                    $deger = implode(', ', $deger);
                }

                $degerkaydet = $db->prepare("INSERT INTO form_degerler SET
                    basvuru_id=:basvuru_id,
                    alan_id=:alan_id,
                    deger=:deger
                ");
                $degerkaydet->execute(array(
                    'basvuru_id' => $basvuru_id,
                    'alan_id' => $alan_id,
                    'deger' => $deger,
                ));
            }
        }

        // 3. Dosyaları İşle
        foreach ($_FILES as $key => $file) {
            if (!is_array($file) || !isset($file['error']) || $file['error'] !== 0) {
                continue;
            }
            $parts = explode('_', $key);
            if (count($parts) == 2 && $parts[0] == 'dosya') {
                $alan_id = $parts[1];

                $uploads_dir = __DIR__ . '/upload/forms';
                @mkdir($uploads_dir, 0777, true);

                $tmp_name = $file['tmp_name'];
                $name = basename($file['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx');

                if (in_array($ext, $allowed)) {
                    $uniq_name = uniqid() . '.' . $ext;
                    $path_abs = $uploads_dir . '/' . $uniq_name;
                    $path_db = 'upload/forms/' . $uniq_name;
                    move_uploaded_file($tmp_name, $path_abs);

                    $degerkaydet = $db->prepare("INSERT INTO form_degerler SET
                        basvuru_id=:basvuru_id,
                        alan_id=:alan_id,
                        deger=:deger
                    ");
                    $degerkaydet->execute(array(
                        'basvuru_id' => $basvuru_id,
                        'alan_id' => $alan_id,
                        'deger' => $path_db,
                    ));
                }
            }
        }

        // --- ORTAK PANEL ENTEGRASYONU BAŞLANGIÇ ---
        try {
            $ayarSor = $db->prepare("SELECT * FROM ayar WHERE ayar_id=0");
            $ayarSor->execute();
            $ayarCek = $ayarSor->fetch(PDO::FETCH_ASSOC);

            if (isset($ayarCek['ayar_common_panel_status']) && $ayarCek['ayar_common_panel_status'] == 1) {
                if (file_exists(__DIR__ . '/common_panel_form_sender.php')) {
                    include_once __DIR__ . '/common_panel_form_sender.php';

                    $site_origin = isset($ayarCek['ayar_siteurl']) ? $ayarCek['ayar_siteurl'] : $_SERVER['HTTP_HOST'];

                    $formAdSor = $db->prepare("SELECT form_baslik FROM formlar WHERE form_id=:id");
                    $formAdSor->execute(['id' => $form_id]);
                    $formAd = $formAdSor->fetchColumn();

                    $fieldsData = array();

                    if (isset($_POST['alan']) && is_array($_POST['alan'])) {
                        foreach ($_POST['alan'] as $alan_id => $deger) {
                            $alanSor = $db->prepare("SELECT alan_baslik FROM form_alanlari WHERE alan_id=:id");
                            $alanSor->execute(['id' => $alan_id]);
                            $key = $alanSor->fetchColumn() ?: "Alan #$alan_id";

                            if (is_array($deger)) {
                                $deger = implode(', ', $deger);
                            }
                            $fieldsData[$key] = $deger;
                        }
                    }

                    $commonFormData = array(
                        'site_origin' => $site_origin,
                        'form_name'   => $formAd,
                        'fields'      => $fieldsData,
                        'files'       => array(),
                    );

                    $dosyaSor = $db->prepare("SELECT alan_id, deger FROM form_degerler WHERE basvuru_id=:id AND deger LIKE 'upload/forms/%'");
                    $dosyaSor->execute(['id' => $basvuru_id]);
                    $dosyalar = $dosyaSor->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($dosyalar as $d) {
                        $f_path = $d['deger'];
                        $f_abs = preg_match('#^[A-Za-z]:[/\\\\]|^/#', (string) $f_path) ? $f_path : (__DIR__ . '/' . ltrim(str_replace('\\', '/', $f_path), '/'));
                        if (file_exists($f_abs)) {
                            $commonFormData['files']['dosya_' . $d['alan_id']] = $f_abs;
                        }
                    }

                    sendFormToCommonPanel($commonFormData, $ayarCek);
                }
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/form_error_log.txt', date('Y-m-d H:i:s') . ' Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        }
        // --- ORTAK PANEL ENTEGRASYONU BİTİŞ ---

        $redirectUrl = form_islem_return_url($db, $form_id);
        $sep = (strpos($redirectUrl, '?') !== false) ? '&' : '?';
        header('Location: ' . $redirectUrl . $sep . 'durum=ok');
        exit;
    }

    $back = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : form_islem_return_url($db, $form_id);
    $sep = (strpos($back, '?') !== false) ? '&' : '?';
    header('Location: ' . $back . $sep . 'durum=no');
    exit;
}
