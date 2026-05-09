<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="wrapper">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="nav-breadcrumb" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo lang_base_url(); ?>"><?php echo trans("home"); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Support</li>
                    </ol>
                </nav>
                <h1 class="page-title">Azura Support</h1>
                <div class="page-text-content post-text-responsive m-b-20">
                    <p>
                        Need help with your Azura account, orders, or payments? Our support team is here to help.
                    </p>
                    <p>
                        For faster assistance, include your account email and order number when you contact us.
                    </p>
                </div>
            </div>

            <div class="col-12">
                <?php $this->load->view('partials/_messages'); ?>
            </div>

            <div class="col-12 col-md-6">
                <div class="page-text-content post-text-responsive">
                    <h3>Contact channels</h3>
                    <ul>
                        <?php if (!empty($this->settings->contact_email)): ?>
                            <li>Email: <a href="mailto:<?php echo html_escape($this->settings->contact_email); ?>"><?php echo html_escape($this->settings->contact_email); ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($this->settings->contact_phone)): ?>
                            <li>Phone: <a href="tel:<?php echo html_escape($this->settings->contact_phone); ?>"><?php echo html_escape($this->settings->contact_phone); ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($this->settings->contact_address)): ?>
                            <li>Address: <?php echo html_escape($this->settings->contact_address); ?></li>
                        <?php endif; ?>
                    </ul>
                    <p>
                        You can also use our contact form: <a href="<?php echo generate_url('contact'); ?>">Contact us</a>.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="page-text-content post-text-responsive">
                    <h3>Common support topics</h3>
                    <ul>
                        <li>Checkout and payment verification</li>
                        <li>Order status and delivery follow-up</li>
                        <li>Login and account access issues</li>
                        <li>Refund and dispute guidance</li>
                    </ul>
                    <h3>Response time</h3>
                    <p>
                        We aim to respond to support requests within 24-48 hours on business days.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
