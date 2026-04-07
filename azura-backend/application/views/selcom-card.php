<?php require_once(APPPATH . 'libraries/OAuth.php'); ?>
<iframe src="<?= base64_decode($this->session->flashdata('payment_gateway_url')); ?>" width="100%" height="900px"  scrolling="no" frameBorder="0">
    <p><?= lang('browserUnableToLoadText') ?></p>
</iframe>
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