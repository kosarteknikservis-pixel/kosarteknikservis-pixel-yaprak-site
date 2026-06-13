<?php
/**
 * Paraşüt API v4 — OAuth2, cari ve satış faturası.
 * @see https://apidocs.parasut.com/
 */

class ParasutV4Client
{
    public const TOKEN_URL = 'https://api.parasut.com/oauth/token';
    public const API_BASE = 'https://api.parasut.com/v4/';

    /** @var PDO */
    private $db;
    /** @var array */
    private $cfg;

    public function __construct($db)
    {
        $this->db = $db;
        $st = $db->query('SELECT * FROM ayar WHERE ayar_id=0');
        $this->cfg = $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function enabled(): bool
    {
        return !empty($this->cfg['ayar_parasut_enabled']);
    }

    public function configured(): bool
    {
        if (!$this->enabled()) {
            return false;
        }
        $need = ['ayar_parasut_company_id', 'ayar_parasut_client_id', 'ayar_parasut_client_secret', 'ayar_parasut_username', 'ayar_parasut_password'];
        foreach ($need as $k) {
            if (trim((string) ($this->cfg[$k] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    private function requestToken(array $body): array
    {
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string) $raw, true);
        if ($code >= 200 && $code < 300 && is_array($json) && !empty($json['access_token'])) {
            return $json;
        }
        $err = is_array($json) && isset($json['error_description']) ? (string) $json['error_description'] : (string) $raw;
        if ($err === '') {
            $err = 'HTTP ' . $code;
        }
        throw new RuntimeException('Paraşüt OAuth: ' . $err);
    }

    private function persistTokens(array $tok): void
    {
        $exp = time() + (int) ($tok['expires_in'] ?? 3600) - 90;
        $refresh = (string) ($tok['refresh_token'] ?? '');
        if ($refresh === '') {
            $refresh = (string) ($this->cfg['ayar_parasut_refresh_token'] ?? '');
        }
        $u = $this->db->prepare('UPDATE ayar SET ayar_parasut_access_token=:a, ayar_parasut_refresh_token=:r, ayar_parasut_token_expires_at=:e WHERE ayar_id=0');
        $u->execute([
            'a' => (string) ($tok['access_token'] ?? ''),
            'r' => $refresh,
            'e' => $exp,
        ]);
        $this->cfg['ayar_parasut_access_token'] = (string) ($tok['access_token'] ?? '');
        $this->cfg['ayar_parasut_refresh_token'] = $refresh;
        $this->cfg['ayar_parasut_token_expires_at'] = $exp;
    }

    public function ensureAccessToken(): string
    {
        $now = time();
        $access = (string) ($this->cfg['ayar_parasut_access_token'] ?? '');
        $exp = (int) ($this->cfg['ayar_parasut_token_expires_at'] ?? 0);
        $refresh = (string) ($this->cfg['ayar_parasut_refresh_token'] ?? '');
        if ($access !== '' && $exp > $now) {
            return $access;
        }
        if ($refresh !== '') {
            try {
                $tok = $this->requestToken([
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->cfg['ayar_parasut_client_id'],
                    'client_secret' => $this->cfg['ayar_parasut_client_secret'],
                    'refresh_token' => $refresh,
                ]);
                $this->persistTokens($tok);
                return (string) $tok['access_token'];
            } catch (Throwable $e) {
            }
        }
        $tok = $this->requestToken([
            'grant_type' => 'password',
            'client_id' => $this->cfg['ayar_parasut_client_id'],
            'client_secret' => $this->cfg['ayar_parasut_client_secret'],
            'username' => $this->cfg['ayar_parasut_username'],
            'password' => $this->cfg['ayar_parasut_password'],
            'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
        ]);
        $this->persistTokens($tok);
        return (string) $tok['access_token'];
    }

    public function api(string $method, string $path, ?array $jsonBody = null): array
    {
        $this->ensureAccessToken();
        $token = (string) ($this->cfg['ayar_parasut_access_token'] ?? '');
        $company = trim((string) $this->cfg['ayar_parasut_company_id']);
        $url = self::API_BASE . rawurlencode($company) . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/vnd.api+json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 90,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string) $raw, true);
        if ($code >= 200 && $code < 300) {
            return is_array($decoded) ? $decoded : [];
        }
        $msg = $raw;
        if (is_array($decoded) && isset($decoded['errors'][0]['detail'])) {
            $msg = (string) $decoded['errors'][0]['detail'];
        }
        throw new RuntimeException('Paraşüt API (' . $code . '): ' . mb_substr($msg, 0, 800));
    }

    public function getOrCreateWebProduct(): string
    {
        $pid = trim((string) ($this->cfg['ayar_parasut_product_id'] ?? ''));
        if ($pid !== '') {
            return $pid;
        }
        $body = [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => 'Web sitesi siparişi',
                    'code' => 'WEB_ORDER_PANEL',
                ],
            ],
        ];
        try {
            $res = $this->api('POST', 'products', $body);
        } catch (RuntimeException $e) {
            $res = $this->api('GET', 'products?filter[code]=WEB_ORDER_PANEL&page[size]=1');
            $pid = $res['data'][0]['id'] ?? '';
            if ($pid !== '') {
                $q = $this->db->prepare('UPDATE ayar SET ayar_parasut_product_id=:p WHERE ayar_id=0');
                $q->execute(['p' => $pid]);
                $this->cfg['ayar_parasut_product_id'] = $pid;
                return $pid;
            }
            throw $e;
        }
        $id = (string) ($res['data']['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Paraşüt ürün kaydı oluşturulamadı.');
        }
        $q = $this->db->prepare('UPDATE ayar SET ayar_parasut_product_id=:p WHERE ayar_id=0');
        $q->execute(['p' => $id]);
        $this->cfg['ayar_parasut_product_id'] = $id;
        return $id;
    }

    /**
     * Sipariş ürün metninden fatura satırı başlığı (Paraşüt max 255).
     */
    private function siparisLineTitle(array $s): string
    {
        $raw = trim(strip_tags((string) ($s['siparis_urun'] ?? '')));
        $raw = preg_replace('/\|[0-9.]+/', '', $raw);
        $raw = preg_replace('/\s+/u', ' ', $raw);
        $raw = str_replace(' | ', ' · ', $raw);
        if ($raw === '') {
            $raw = 'Sipariş #' . (int) ($s['siparis_id'] ?? 0);
        }
        return mb_substr($raw, 0, 255);
    }

    private function isKdvDahil(): bool
    {
        if (!array_key_exists('ayar_parasut_kdv_dahil', $this->cfg)) {
            return true;
        }
        return (int) $this->cfg['ayar_parasut_kdv_dahil'] === 1;
    }

    /**
     * KDV dahil brüt tutardan satır birim fiyatı (KDV hariç) ve satır KDV oranı.
     * Paraşüt net × (1 + KDV/100) ≈ brüt olacak şekilde net gönderilir.
     *
     * @return array{0: float, 1: float} [unit_price_excl_vat, vat_rate_percent]
     */
    private function parasutLinePriceAndVat(float $grossInclVat, float $vatRatePct, bool $grossIsTaxInclusive): array
    {
        $vatRatePct = max(0.0, $vatRatePct);
        if (!$grossIsTaxInclusive) {
            return [round($grossInclVat, 4), round($vatRatePct, 2)];
        }
        if ($vatRatePct <= 0.0) {
            return [round($grossInclVat, 2), 0.0];
        }
        $net = $grossInclVat / (1.0 + $vatRatePct / 100.0);
        return [round($net, 4), round($vatRatePct, 2)];
    }

    /**
     * Sipariş bazlı ürün: kod WS_{id} — isim sipariş ürününden (Paraşüt ürün kartı KDV’si satırı ezmesin diye önce ürünsüz denenir).
     */
    private function getOrCreateProductForSiparis(array $s, string $lineTitle): string
    {
        $sid = (int) ($s['siparis_id'] ?? 0);
        $code = 'WS_' . $sid;
        $name = mb_substr($lineTitle, 0, 200);
        if ($name === '') {
            $name = 'Sipariş #' . $sid;
        }
        try {
            $res = $this->api('GET', 'products?filter[code]=' . rawurlencode($code) . '&page[size]=1');
            $found = $res['data'][0]['id'] ?? '';
            if ($found !== '') {
                return (string) $found;
            }
        } catch (Throwable $e) {
        }
        $body = [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => $name,
                    'code' => $code,
                ],
            ],
        ];
        $res = $this->api('POST', 'products', $body);
        $id = (string) ($res['data']['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Paraşüt ürün kaydı oluşturulamadı (WS_' . $sid . ').');
        }
        return $id;
    }

    /**
     * @param string|false $productId Paraşüt ürün id veya ürün bağlamadan denemek için false
     */
    private function buildSalesInvoicePayload(string $contactId, string $issue, array $detailAttrs, $productId)
    {
        $detail = [
            'type' => 'sales_invoice_details',
            'attributes' => $detailAttrs,
        ];
        if ($productId !== false && $productId !== '') {
            $detail['relationships'] = [
                'product' => ['data' => ['id' => (string) $productId, 'type' => 'products']],
            ];
        }
        return [
            'data' => [
                'type' => 'sales_invoices',
                'attributes' => [
                    'item_type' => 'invoice',
                    'issue_date' => $issue,
                    'due_date' => $issue,
                    'currency' => 'TRL',
                ],
                'relationships' => [
                    'contact' => ['data' => ['id' => $contactId, 'type' => 'contacts']],
                    'details' => ['data' => [$detail]],
                ],
            ],
        ];
    }

    /**
     * Kurumsal fatura bilgisi var mı? (ticari cari — VKN, ünvan, VD, fatura adresi)
     */
    private function siparisHasKurumsalFatura(array $s): bool
    {
        $keys = ['siparis_fatura_vn', 'siparis_fatura_vd', 'siparis_fatura_unvan', 'siparis_fatura_adres'];
        foreach ($keys as $k) {
            if (trim((string) ($s[$k] ?? '')) !== '') {
                return true;
            }
        }
        return false;
    }

    private function parasutDigitsOnly(string $v, int $maxLen): string
    {
        $d = preg_replace('/[^0-9]/', '', $v);
        if ($d === '') {
            return '';
        }
        return mb_substr($d, 0, $maxLen);
    }

    public function createContactForOrder(array $s): string
    {
        $ad = trim((string) ($s['siparis_ad'] ?? ''));
        if ($ad === '') {
            $ad = 'Müşteri';
        }
        $vn = trim((string) ($s['siparis_fatura_vn'] ?? ''));
        $unvan = trim((string) ($s['siparis_fatura_unvan'] ?? ''));
        $vd = trim((string) ($s['siparis_fatura_vd'] ?? ''));
        $fad = trim((string) ($s['siparis_fatura_adres'] ?? ''));

        $kurumsal = $this->siparisHasKurumsalFatura($s);
        $bireyselNoRaw = trim((string) ($this->cfg['ayar_parasut_bireysel_vergi_no'] ?? '11111111111'));
        if ($bireyselNoRaw === '') {
            $bireyselNoRaw = '11111111111';
        }
        $bireyselNo = $this->parasutDigitsOnly($bireyselNoRaw, 11);
        if ($bireyselNo === '') {
            $bireyselNo = '11111111111';
        }

        if ($kurumsal) {
            $name = $unvan !== '' ? $unvan : $ad;
            $attrs = [
                'name' => mb_substr($name, 0, 255),
                'contact_type' => 'company',
                'phone' => mb_substr(preg_replace('/[^0-9+]/', '', (string) ($s['siparis_tel'] ?? '')), 0, 40),
                'city' => mb_substr((string) ($s['siparis_il'] ?? ''), 0, 64),
                'district' => mb_substr((string) ($s['siparis_ilce'] ?? ''), 0, 64),
                'address' => mb_substr($fad !== '' ? $fad : (string) ($s['siparis_adres'] ?? ''), 0, 500),
                'account_type' => 'customer',
            ];
            $vnDigits = $this->parasutDigitsOnly($vn, 11);
            if ($vnDigits !== '') {
                $attrs['tax_number'] = $vnDigits;
            } else {
                $attrs['tax_number'] = mb_substr($bireyselNo, 0, 10);
                if ($attrs['tax_number'] === '') {
                    $attrs['tax_number'] = '1111111111';
                }
            }
            if ($vd !== '') {
                $attrs['tax_office'] = mb_substr($vd, 0, 120);
            }
        } else {
            $attrs = [
                'name' => mb_substr($ad, 0, 255),
                'contact_type' => 'person',
                'phone' => mb_substr(preg_replace('/[^0-9+]/', '', (string) ($s['siparis_tel'] ?? '')), 0, 40),
                'city' => mb_substr((string) ($s['siparis_il'] ?? ''), 0, 64),
                'district' => mb_substr((string) ($s['siparis_ilce'] ?? ''), 0, 64),
                'address' => mb_substr((string) ($s['siparis_adres'] ?? ''), 0, 500),
                'account_type' => 'customer',
                'tax_number' => $bireyselNo,
            ];
        }

        $body = ['data' => ['type' => 'contacts', 'attributes' => $attrs]];
        $res = $this->api('POST', 'contacts', $body);
        $id = (string) ($res['data']['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Paraşüt cari kartı oluşturulamadı.');
        }
        return $id;
    }

    public function createSalesInvoice(string $contactId, array $s): string
    {
        $fiyat = (float) ($s['siparis_fiyat'] ?? 0);
        $ts = strtotime((string) ($s['siparis_tarih'] ?? 'now'));
        $issue = $ts ? date('Y-m-d', $ts) : date('Y-m-d');

        $lineTitle = $this->siparisLineTitle($s);
        $vatCfg = (float) ($this->cfg['ayar_parasut_vat_rate'] ?? 0);
        [$unitPrice, $vatLine] = $this->parasutLinePriceAndVat($fiyat, $vatCfg, $this->isKdvDahil());

        $detailAttrs = [
            'quantity' => 1.0,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatLine,
            'description' => $lineTitle,
        ];

        $payload = $this->buildSalesInvoicePayload($contactId, $issue, $detailAttrs, false);
        try {
            $res = $this->api('POST', 'sales_invoices', $payload);
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '(422)') !== false || strpos($msg, '(400)') !== false) {
                $pid = $this->getOrCreateProductForSiparis($s, $lineTitle);
                $payload = $this->buildSalesInvoicePayload($contactId, $issue, $detailAttrs, $pid);
                $res = $this->api('POST', 'sales_invoices', $payload);
            } else {
                throw $e;
            }
        }

        $id = (string) ($res['data']['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Paraşüt satış faturası oluşturulamadı.');
        }
        return $id;
    }

    public function sendSiparis(array $row, bool $force = false): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'error' => 'Paraşüt kapalı veya ayarlar eksik (Genel Ayarlar → Paraşüt).'];
        }
        $existing = trim((string) ($row['siparis_parasut_invoice_id'] ?? ''));
        if ($existing !== '' && !$force) {
            return ['ok' => false, 'error' => 'Bu sipariş zaten Paraşüt’e gönderilmiş (fatura kayıt id: ' . $existing . ').'];
        }
        try {
            $contactId = $this->createContactForOrder($row);
            $invId = $this->createSalesInvoice($contactId, $row);
            $upd = $this->db->prepare('UPDATE siparis SET siparis_parasut_invoice_id=:p WHERE siparis_id=:id');
            $upd->execute(['p' => $invId, 'id' => $row['siparis_id']]);
            return ['ok' => true, 'parasut_invoice_id' => $invId, 'parasut_contact_id' => $contactId];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
