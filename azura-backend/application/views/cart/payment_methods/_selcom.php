<?php defined('BASEPATH') or exit('No direct script access allowed');
if (!empty($payment_gateway) && $payment_gateway->name_key == "selcom"): ?>
    <div class="row">
        <div class="col-12">
            <?php $this->load->view('product/_messages'); ?>
        </div>
    </div>
    <?php echo form_open('selcom-payment-post'); ?>
    <input type="hidden" name="mds_payment_type" value="<?= html_escape($mds_payment_type); ?>">
    <div id="payment-button-container" class="payment-button-cnt">
        <div class="payment-icons-container">
            <label class="payment-icons">
                <?php $logos = @explode(',', $payment_gateway->logos);
                if (!empty($logos) && item_count($logos) > 0):
                    foreach ($logos as $logo): ?>
                        <img src="<?php echo base_url(); ?>assets/img/payment/<?= html_escape(trim($logo)); ?>.svg" alt="<?= html_escape(trim($logo)); ?>">
                    <?php endforeach;
                endif; ?>
            </label>
        </div>
        <p class="p-complete-payment text-muted"><?php echo trans("msg_complete_payment"); ?></p>
        <button type="submit" class="btn btn-lg btn-payment btn-custom"><?= trans("pay"); ?>&nbsp;<?= price_decimal($total_amount, $currency); ?></button>
    </div>
    <?php echo form_close(); ?>
<?php endif; ?>
