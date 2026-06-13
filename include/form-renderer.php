<?php
// Form Render Component
// Expects $form_id or $form_slug to be set
if (isset($form_slug)) {
    $formsor = $db->prepare("SELECT * FROM formlar WHERE form_slug=:slug AND form_durum=1");
    $formsor->execute(array('slug' => $form_slug));
    $formcek = $formsor->fetch(PDO::FETCH_ASSOC);
} elseif (isset($form_id)) {
    $formsor = $db->prepare("SELECT * FROM formlar WHERE form_id=:id AND form_durum=1");
    $formsor->execute(array('id' => $form_id));
    $formcek = $formsor->fetch(PDO::FETCH_ASSOC);
}

if (!isset($formcek) || !$formcek) {
    echo '<div class="alert alert-warning">Form bulunamadı veya pasif durumda.</div>';
} else {
    $form_id = $formcek['form_id'];
?>

<!-- Form UX Styles -->
<style>
    .dynamic-form-container {
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin: 20px 0;
        border: 1px solid #f1f5f9;
        font-family: 'Inter', -apple-system, sans-serif;
    }
    .dynamic-form-container h3 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .dynamic-form-container p {
        color: #64748b;
        font-size: 1.1rem;
    }
    .dynamic-form .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        color: #334155;
        font-size: 0.95rem;
        transition: color 0.2s;
    }
    .dynamic-form .form-control {
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #f8fafc;
    }
    .dynamic-form .form-control:focus {
        border-color: #3b82f6;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    .dynamic-form-container .form-group:focus-within label {
        color: #3b82f6;
    }
    
    /* Auto-growing Textarea */
    .dynamic-form textarea.form-control {
        min-height: 100px;
        field-sizing: content; /* Modern browser support */
        resize: none;
    }

    /* Custom Radio & Checkbox Styling */
    .custom-selection-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 5px;
    }
    .custom-selection-item {
        position: relative;
    }
    .custom-selection-item input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    .custom-selection-label {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 0 !important;
        font-weight: 600 !important;
        color: #475569 !important;
    }
    .custom-selection-item:hover .custom-selection-label {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
    .custom-selection-item input:checked + .custom-selection-label {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #1d4ed8 !important;
    }
    .selection-indicator {
        width: 20px;
        height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 6px; /* Box for checkbox */
        margin-right: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fff;
        transition: all 0.2s;
    }
    .custom-selection-item[data-type="radio"] .selection-indicator {
        border-radius: 50%;
    }
    .custom-selection-item input:checked + .custom-selection-label .selection-indicator {
        border-color: #3b82f6;
        background: #3b82f6;
    }
    .selection-indicator::after {
        content: '';
        width: 8px;
        height: 8px;
        background: #fff;
        border-radius: 50%;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s;
    }
    .custom-selection-item[data-type="checkbox"] .selection-indicator::after {
        content: '\f00c';
        font-family: 'FontAwesome';
        background: none;
        font-size: 10px;
        border-radius: 0;
    }
    .custom-selection-item input:checked + .custom-selection-label .selection-indicator::after {
        opacity: 1;
        transform: scale(1);
    }

    .btn-submit-premium {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border: none;
        padding: 18px 32px;
        border-radius: 16px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
    }
    .btn-submit-premium:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 25px rgba(37, 99, 235, 0.4);
        opacity: 0.98;
    }
    .btn-submit-premium:active {
        transform: translateY(0) scale(0.98);
    }
</style>

<div class="dynamic-form-container">
    <div class="text-center mb-4">
        <h3><?php echo $formcek['form_baslik']; ?></h3>
        <?php if(!empty($formcek['form_aciklama'])) { echo '<p>'.$formcek['form_aciklama'].'</p>'; } ?>
    </div>
    
    <form action="<?php echo htmlspecialchars(defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/form-islem.php' : 'form-islem.php', ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data" class="dynamic-form">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" name="dynamic_form_submit" value="1">
        
        <?php 
        $alansor = $db->prepare("SELECT * FROM form_alanlari WHERE form_id=:id ORDER BY alan_sira ASC");
        $alansor->execute(array('id' => $form_id));
        while($alancek = $alansor->fetch(PDO::FETCH_ASSOC)) {
            $required = $alancek['alan_zorunlu'] ? 'required' : '';
            $req_star = $alancek['alan_zorunlu'] ? ' <span class="text-danger">*</span>' : '';
            $alan_id = $alancek['alan_id'];
            $alan_tip = $alancek['alan_tip'];
        ?>
        <div class="form-group mb-4">
            <label class="form-label font-weight-bold"><?php echo $alancek['alan_baslik'] . $req_star; ?></label>
            
            <?php if(in_array($alan_tip, ['text', 'email', 'tel', 'number'])) { 
                $input_mode = ($alan_tip == 'tel' || $alan_tip == 'number') ? 'inputmode="numeric" pattern="[0-9]*"' : '';
                $max_len = ($alan_tip == 'tel') ? 'maxlength="11"' : '';
            ?>
                <input type="<?php echo $alan_tip; ?>" 
                       name="alan[<?php echo $alan_id; ?>]" 
                       class="form-control <?php echo ($alan_tip == 'tel')?'tel-validate':''; ?>" 
                       <?php echo $input_mode; ?> 
                       <?php echo $max_len; ?> 
                       <?php echo $required; ?>>
                
            <?php } elseif($alan_tip == 'textarea') { ?>
                <textarea name="alan[<?php echo $alan_id; ?>]" 
                          class="form-control auto-grow" 
                          rows="4" 
                          placeholder="Mesajınızı buraya yazınız..." 
                          <?php echo $required; ?>></textarea>
                
            <?php } elseif($alan_tip == 'file') { ?>
                <div class="custom-file-upload">
                    <input type="file" name="dosya_<?php echo $alan_id; ?>" class="form-control" <?php echo $required; ?>>
                    <small class="text-muted mt-2 d-block">İzin verilen formatlar: PDF, DOC, JPG, PNG. (Maks: 5MB)</small>
                </div>
                
            <?php } elseif($alan_tip == 'select') { ?>
                <select name="alan[<?php echo $alan_id; ?>]" class="form-control" <?php echo $required; ?>>
                    <option value="">Lütfen seçim yapınız...</option>
                    <?php 
                    $secenekler = explode(',', (string) ($alancek['alan_secenekler'] ?? ''));
                    foreach ($secenekler as $sec) {
                        $val = trim($sec);
                        if ($val === '') {
                            continue;
                        }
                        echo '<option value="'.htmlspecialchars($val, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($val, ENT_QUOTES, 'UTF-8').'</option>';
                    }
                    ?>
                </select>
                
            <?php } elseif($alan_tip == 'radio' || $alan_tip == 'checkbox') { ?>
                <div class="custom-selection-group">
                    <?php 
                    $secenekler = explode(',', (string) ($alancek['alan_secenekler'] ?? ''));
                    foreach ($secenekler as $index => $sec) {
                        $val = trim($sec);
                        if ($val === '') {
                            continue;
                        }
                        $valEsc = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                        $input_id = "alan_{$alan_id}_{$index}";
                        $input_name = ($alan_tip == 'checkbox') ? "alan[{$alan_id}][]" : "alan[{$alan_id}]";
                        ?>
                        <div class="custom-selection-item" data-type="<?php echo $alan_tip; ?>">
                            <input type="<?php echo $alan_tip; ?>" 
                                   name="<?php echo $input_name; ?>" 
                                   id="<?php echo $input_id; ?>" 
                                   value="<?php echo $valEsc; ?>" 
                                   <?php echo ($alan_tip == 'radio')?$required:''; ?>>
                            <label class="custom-selection-label" for="<?php echo $input_id; ?>">
                                <div class="selection-indicator"></div>
                                <?php echo $valEsc; ?>
                            </label>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            <?php } ?>
        </div>
        <?php } ?>
        
        <div class="form-group mt-5">
            <button type="submit" class="btn btn-submit-premium btn-lg btn-block">
                <i class="fa fa-paper-plane mr-2"></i> BİLGİLERİ GÖNDER
            </button>
        </div>
    </form>
</div>

<!-- Form UX Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Phone Validation
    document.querySelectorAll('.tel-validate').forEach(function(el) {
        el.addEventListener('input', function() {
            var value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            this.value = value;
        });
        el.addEventListener('paste', function() {
            var self = this;
            setTimeout(function() {
                self.value = self.value.replace(/\D/g, '').slice(0, 11);
            }, 0);
        });
    });

    // 2. Textarea Auto-Grow (Fallback for older browsers)
    document.querySelectorAll('textarea.auto-grow').forEach(function(el) {
        el.style.overflowY = 'hidden';
        el.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
</script>

<?php } ?>
