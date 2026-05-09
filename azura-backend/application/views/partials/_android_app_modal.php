<?php defined('BASEPATH') or exit('No direct script access allowed');
$apk_rel = 'downloads/azura-android.apk';
$apk_path = FCPATH . $apk_rel;
$apk_href = base_url($apk_rel);
$apk_ready = is_file($apk_path) && filesize($apk_path) > 0;
?>
<div class="modal fade" id="androidAppModal" tabindex="-1" role="dialog" aria-labelledby="androidAppModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered azura-app-modal-dialog" role="document">
        <div class="modal-content azura-app-modal-content">
            <div class="modal-body azura-app-modal-body">
                <button type="button" class="close azura-app-modal-close" data-dismiss="modal" aria-label="Close"><i class="icon-close"></i></button>
                <div class="azura-app-modal-icon-wrap">
                    <span class="azura-app-modal-icon"><i class="icon-download-solid"></i></span>
                </div>
                <h4 class="title azura-app-modal-title" id="androidAppModalTitle"><?= html_escape($this->general_settings->application_name); ?> for Android</h4>
                <p class="azura-app-modal-text">Shop on the go with our mobile app. Download the release build below and open the file on your Android device to install.</p>
                <?php if ($apk_ready): ?>
                    <a class="btn btn-md btn-custom btn-block azura-app-download-btn" href="<?= $apk_href; ?>" download="azura-android.apk">
                        <i class="icon-download"></i>&nbsp; Download APK
                    </a>
                    <p class="azura-app-modal-hint text-muted">ARM64 devices (most phones from 2017+). You may need to allow installs from your browser in Android settings.</p>
                <?php else: ?>
                    <p class="text-muted m-0">The Android package is not available on this server yet. Please check back soon.</p>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary btn-block m-t-15" data-dismiss="modal">Not now</button>
            </div>
        </div>
    </div>
</div>
