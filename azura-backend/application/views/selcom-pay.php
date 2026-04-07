<?php defined('BASEPATH') or exit('no direct script access allowed'); ?>
<div class="container space27" style="margin-bottom:70px;height:300px">
    <div class="">
        <p class="fs-4 mb-0"><?= lang('waitingForPaymentCompletionText') ?></p>
        <p class="fs-6"><?= lang('lookAtYourMobilePhoneText') ?></p>
    </div>
    <div class="mt-2 mb-5">
        <div class="d-flex flex-column justify-content-center align-items-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden"><?= lang('loadingText') ?>...</span>
          </div>
        </div>
        <p class="text-center"><?= lang('waitingPaymentText') ?></p>
    </div>
    <div class="mt-3 pt-3">
        <p class="text-center"><?= lang('haveNotReceivedPaymentRequestText'); ?>?</p>
        <div class="d-lg-flex d-md-flex flex-row justify-content-center align-items-center">
            <form class="form" method="POST" action="<?= site_url('pay') ?>" id="payment-form" class="mx-auto">
                
                <input type="hidden" name="payment_methods" value="<?= $this->session->flashdata('payment_methods'); ?>" />
                <input type="hidden" name="calling_code" value="<?= $this->session->flashdata('calling_code'); ?>" />
                <input type="hidden" name="phone" value="<?= $this->session->flashdata('phone'); ?>" />
                <input type="hidden" name="amount" value="<?= $this->session->flashdata('amount'); ?>" />
                
                <input type="hidden" name="reference_id" value="<?= $this->session->flashdata('reference_id'); ?>" />
                <input type="hidden" name="reference_number" value="<?= $this->session->flashdata('reference_number'); ?>" />
                <input type="hidden" name="meter_number" value="<?= $this->session->flashdata('meter_number'); ?>" />
                
                <input type="hidden" name="name" value="<?= $this->session->flashdata('name'); ?>" />
                <input type="hidden" name="country" value="<?= $this->session->flashdata('country'); ?>" />
                <input type="hidden" name="state_or_region" value="<?= $this->session->flashdata('state_or_region'); ?>" />
                <input type="hidden" name="email" value="<?= $this->session->flashdata('email'); ?>" />
                <input type="hidden" name="postcode_or_pobox" value="<?= $this->session->flashdata('postcode_or_pobox'); ?>" />
                <input type="hidden" name="city" value="<?= $this->session->flashdata('city'); ?>" />
                <input type="hidden" name="address" value="<?= $this->session->flashdata('address'); ?>" />
                
                <div class="btn-container px-1 d-flex flex-column justify-content-center align-items-center">
                    <button type="submit" class="btn btn-primary rounded-5 px-4 mb-4">
                        <b><?= lang('retryText'); ?></b>
                    </button>
                </div>
            </form>
            <form class="form" method="POST" action="<?= site_url('payments') ?>" id="electricity-form" class="mx-auto">
                
                <input type="hidden" name="name" value="<?= $this->session->flashdata('name'); ?>" />
                <input type="hidden" name="meter_number" value="<?= $this->session->flashdata('meter_number'); ?>" />
                <input type="hidden" name="calling_code" value="<?= $this->session->flashdata('calling_code'); ?>" />
                <input type="hidden" name="phone" value="<?= $this->session->flashdata('phone'); ?>" />
                <input type="hidden" name="amount" value="<?= $this->session->flashdata('amount'); ?>" />
                
                <div class="btn-container px-1 d-flex flex-column justify-content-center align-items-center">
                    <button type="submit" class="btn btn-outline-primary rounded-5 px-4 mb-4"><?= lang('changePaymentDetailsText') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
    $check_url = "?order_id=".$this->session->flashdata('order_id')."&reference=".$this->session->flashdata('reference')."&transid=".$this->session->flashdata('transid')."&reference_id=".$this->session->flashdata('reference_id')."&reference_number=".$this->session->flashdata('reference_number')."&meter_number=".$this->session->flashdata('meter_number')."&calling_code=".$this->session->flashdata('calling_code')."&phone=".$this->session->flashdata('phone');
?>
<script type="text/javascript">
    setInterval( () => (
        fetch("<?= site_url('pay')."/check".$check_url ?>", { method: 'GET' })
        .then( (response) => ( response.text() ) )
        .then( (data) => ( parseInt(data || '0') && window.location.replace("<?= site_url('thankyou').$check_url ?>") ) )
    ), 5000);
</script>