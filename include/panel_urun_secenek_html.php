<?php
/**
 * Ürün satırından (urunler tablosu fetch) seçenek alanının HTML'i — index ilk boyama + urun-secenek-getir ile aynı çıktı.
 *
 * Alt seçenek JSON alanları (opsiyonel):
 * - color_hex: vitrinde renkli kare (#rgb, #rrggbb veya #rrggbbaa). Boşsa (ör. beden) seçenek adı metin düğmesi olarak gösterilir.
 * - active: false ise vitrinde ve listede gösterilmez (varsayılan: aktif)
 *
 * @param array $urunSecenek PDO fetch (en az secenekler anahtarı)
 * @return string
 */
function panel_urun_secenek_sanitize_hex($s) {
    $s = trim((string) $s);
    if ($s === '') {
        return '';
    }
    if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $s)) {
        return $s;
    }
    return '';
}

function panel_sub_option_is_active($sub) {
    if (is_object($sub)) {
        if (!isset($sub->active)) {
            return true;
        }
        $a = $sub->active;
        return !($a === false || $a === 0 || $a === '0' || $a === '');
    }
    if (is_array($sub)) {
        if (!array_key_exists('active', $sub)) {
            return true;
        }
        $a = $sub['active'];
        return !($a === false || $a === 0 || $a === '0' || $a === '');
    }
    return true;
}

function panel_sub_get_color_hex($sub) {
    if (is_object($sub) && isset($sub->color_hex)) {
        return (string) $sub->color_hex;
    }
    if (is_array($sub) && isset($sub['color_hex'])) {
        return (string) $sub['color_hex'];
    }
    return '';
}

function panel_build_urun_secenek_html($urunSecenek) {
    if (!is_array($urunSecenek) || empty($urunSecenek['secenekler'])) {
        return '';
    }

    $secenekHtml = '<label class="order-label">Ürün Seçenekleri:</label>';

    $seceneklerArray = json_decode($urunSecenek['secenekler'], true);
    if (!is_array($seceneklerArray)) {
        return '';
    }

    foreach ($seceneklerArray as $secenek) {
        if (!is_array($secenek)) {
            continue;
        }
        $secenekTitle = isset($secenek['title']) ? (string) $secenek['title'] : '';
        $secenekIsRequired = !empty($secenek['is_required']);
        $secenekSub = isset($secenek['sub']) && is_array($secenek['sub']) ? $secenek['sub'] : array();

        $requiredBadge = $secenekIsRequired == true ? ' <span style="color:red;">(*Zorunlu)</span>' : '';
        $groupId = 'usc_' . preg_replace('/[^a-z0-9]+/i', '-', $secenekTitle) . '-' . substr(md5($secenekTitle . '|' . (string) ($urunSecenek['urun_id'] ?? '')), 0, 8);

        $secenekHtml .= "<div class='col-12 col-sm-12 col-lg-12 order-box urun-secenek-field' data-secenek-group='" . htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') . "'>";
        $secenekHtml .= '<label>' . htmlspecialchars($secenekTitle) . $requiredBadge . '</label>';

        $swatchRow = "<div class='urun-secenek-swatches' role='group' aria-label='" . htmlspecialchars($secenekTitle, ENT_QUOTES, 'UTF-8') . "'>";
        $selectHtml = "<select name='secenekler[" . htmlspecialchars($secenekTitle, ENT_QUOTES, 'UTF-8') . "]' class='form-control urun-secenek-native-select' required>";
        $selectHtml .= '<option selected value="">-Lütfen Seçiniz-</option>';

        $swatchIdx = 0;
        foreach ($secenekSub as $sub) {
                if (!panel_sub_option_is_active($sub)) {
                    continue;
                }
                $subTitle = is_array($sub) && isset($sub['title']) ? (string) $sub['title'] : (is_object($sub) && isset($sub->title) ? (string) $sub->title : '');
                $subValue = is_array($sub) && isset($sub['value']) ? $sub['value'] : (is_object($sub) && isset($sub->value) ? $sub->value : '');

                $cleanSubValue = '';
                if (!empty($subValue)) {
                    $cleanSubValue = preg_replace('/[^0-9.]/', '', strval($subValue));
                }

                $optionValue = htmlspecialchars($subTitle . (!empty($cleanSubValue) ? '|' . $cleanSubValue : ''), ENT_QUOTES, 'UTF-8');
                $optionText = htmlspecialchars($subTitle . (!empty($cleanSubValue) ? ' +' . $cleanSubValue . ' ₺' : ''), ENT_QUOTES, 'UTF-8');
                $selectHtml .= "<option value=\"" . $optionValue . "\">" . $optionText . '</option>';

                $hexRaw = panel_sub_get_color_hex($sub);
                $hexOk = panel_urun_secenek_sanitize_hex($hexRaw);
                $swatchStyle = $hexOk !== '' ? 'background-color:' . htmlspecialchars($hexOk, ENT_QUOTES, 'UTF-8') . ';' : '';
                $labelEsc = htmlspecialchars($subTitle, ENT_QUOTES, 'UTF-8');
                if ($hexOk !== '') {
                    $swatchRow .= '<button type="button" class="urun-secenek-swatch" data-opt-idx="' . (int) $swatchIdx . '" aria-label="' . $labelEsc . '" title="' . $labelEsc . '" style="' . $swatchStyle . '"></button>';
                } else {
                    $swatchRow .= '<button type="button" class="urun-secenek-swatch urun-secenek-swatch--chip" data-opt-idx="' . (int) $swatchIdx . '" aria-label="' . $labelEsc . '" title="' . $labelEsc . '"><span class="urun-secenek-swatch-label">' . $labelEsc . '</span></button>';
                }

                $swatchIdx++;
        }
        $swatchRow .= '</div>';
        $selectHtml .= '</select>';

        if ($swatchIdx === 0) {
            $secenekHtml .= '<p class="text-muted" style="margin:0;font-size:0.9rem;">Bu seçenek için aktif alt seçenek yok.</p></div>';
            continue;
        }

        $secenekHtml .= $swatchRow;
        $secenekHtml .= '<p class="urun-secenek-select-hint" style="margin:8px 0 4px;font-size:0.8rem;color:#64748b;">Listeden de seçebilirsiniz:</p>';
        $secenekHtml .= $selectHtml;
        $secenekHtml .= '</div>';
    }

    return $secenekHtml;
}
