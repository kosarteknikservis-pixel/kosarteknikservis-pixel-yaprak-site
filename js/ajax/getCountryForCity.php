<?php
/**
 * İl seçimine göre ilçe listesi (tek sorgu; hafif DB bootstrap + session kilidi yok).
 */
require_once __DIR__ . '/../../xnull/controller/ajax_public_db.php';

if (!isset($_POST['city_id'])) {
	exit;
}
$getCityID = (int) $_POST['city_id'];
if ($getCityID < 1) {
	echo '<option value="">İl bulunamadı</option>';
	exit;
}

$ilcesor = $db->prepare('SELECT * FROM ilce WHERE il_id = :id ORDER BY ilce_adi ASC');
$ilcesor->execute(array('id' => $getCityID));
$rows = $ilcesor->fetchAll(PDO::FETCH_ASSOC);

if ($rows === []) {
	echo '<option value="">Bu il için ilçe bulunamadı</option>';
	exit;
}

echo '<option value="">İlçe seçiniz.</option>';
foreach ($rows as $ilcecek) {
	$ilce_key = isset($ilcecek['ilce_key']) && $ilcecek['ilce_key'] !== '' && $ilcecek['ilce_key'] !== null
		? $ilcecek['ilce_key']
		: $ilcecek['id'];
	?>
<option data-id="<?php echo htmlspecialchars((string) $ilce_key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $ilcecek['ilce_adi'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $ilcecek['ilce_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
<?php
}
