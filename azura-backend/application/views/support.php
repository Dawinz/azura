<?php defined('BASEPATH') or exit('No direct script access allowed');
$page_terms = get_page_by_default_name('terms_conditions', $this->selected_lang->id);
?>
<div id="wrapper">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="nav-breadcrumb" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo lang_base_url(); ?>"><?php echo trans('home'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Support</li>
                    </ol>
                </nav>
                <h1 class="page-title">Support &amp; help center</h1>
                <div class="page-text-content post-text-responsive m-b-20">
                    <p class="lead">
                        Welcome to <?php echo html_escape($this->app_name); ?> support. Whether you shop on our website or use the Azura mobile app,
                        this page explains how to get help, what to expect when you order, and how we handle common questions about accounts,
                        payments, and deliveries.
                    </p>
                    <p>
                        For the fastest resolution, please include your <strong>registered email address</strong> and, if your question is about an order,
                        your <strong>order number</strong> or the <strong>approximate date of purchase</strong> when you contact us.
                    </p>
                </div>
            </div>

            <div class="col-12 m-b-20">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Quick links</h2>
                    <ul class="list-inline m-b-0">
                        <li class="list-inline-item m-b-5"><a href="<?php echo generate_url('contact'); ?>"><?php echo trans('contact'); ?></a></li>
                        <li class="list-inline-item m-b-5"><span aria-hidden="true">·</span></li>
                        <li class="list-inline-item m-b-5"><a href="<?php echo lang_base_url(); ?>privacy-policy">Privacy Policy</a></li>
                        <?php if (!empty($page_terms)): ?>
                            <li class="list-inline-item m-b-5"><span aria-hidden="true">·</span></li>
                            <li class="list-inline-item m-b-5"><a href="<?php echo generate_url($page_terms->page_default_name); ?>"><?php echo html_escape($page_terms->title); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="col-12">
                <?php $this->load->view('partials/_messages'); ?>
            </div>

            <div class="col-12 col-lg-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Contact us</h2>
                    <p>Choose the channel that works best for you. If you use the contact form, we receive your message securely and reply by email.</p>
                    <ul>
                        <?php if (!empty($this->settings->contact_email)): ?>
                            <li><strong>Email:</strong> <a href="mailto:<?php echo html_escape($this->settings->contact_email); ?>"><?php echo html_escape($this->settings->contact_email); ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($this->settings->contact_phone)): ?>
                            <li><strong>Phone:</strong> <a href="tel:<?php echo html_escape(preg_replace('/\s+/', '', (string) $this->settings->contact_phone)); ?>"><?php echo html_escape($this->settings->contact_phone); ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($this->settings->contact_address)): ?>
                            <li><strong>Business address:</strong> <?php echo html_escape($this->settings->contact_address); ?></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (empty($this->settings->contact_email) && empty($this->settings->contact_phone) && empty($this->settings->contact_address)): ?>
                        <p class="text-muted">Contact details are configured by the site administrator. Please use the contact form below.</p>
                    <?php endif; ?>
                    <p class="m-b-0">
                        <a class="btn btn-md btn-custom" href="<?php echo generate_url('contact'); ?>"><?php echo trans('contact'); ?></a>
                    </p>
                </div>
            </div>

            <div class="col-12 col-lg-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Response times</h2>
                    <p>
                        We aim to acknowledge support requests within <strong>24–48 hours</strong> on business days. Complex cases
                        (for example payment investigations or delivery disputes) may take longer while we work with partners and sellers.
                    </p>
                    <p class="m-b-0">
                        Messages sent outside business hours or on public holidays are queued and handled in order of receipt.
                    </p>
                </div>
            </div>

            <div class="col-12 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Shopping on <?php echo html_escape($this->app_name); ?></h2>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <h3 class="h4">Browsing &amp; cart</h3>
                            <ul>
                                <li>Use search and categories to find products; check size, colour, and description before checkout.</li>
                                <li>Your cart is tied to your session or account—sign in to keep items across devices where supported.</li>
                                <li>Prices and availability can change; the checkout screen shows the amount you agree to pay.</li>
                            </ul>
                        </div>
                        <div class="col-12 col-md-6">
                            <h3 class="h4">Checkout</h3>
                            <ul>
                                <li>Enter accurate shipping and contact details—carriers use the address you confirm at checkout.</li>
                                <li>Follow on-screen instructions to complete payment; keep any confirmation or reference from your bank or wallet.</li>
                                <li>If checkout fails, wait a few minutes before retrying and avoid placing duplicate orders.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Orders &amp; delivery</h2>
                    <ul>
                        <li>After payment, you should receive an order confirmation by email when email delivery is enabled on your account.</li>
                        <li>Delivery times depend on the seller, product type, and courier—estimates shown on the product or checkout page are indicative only.</li>
                        <li>If a parcel is delayed, contact us with your order number; we will help trace it with the seller or carrier where possible.</li>
                        <li>For damaged or missing items, send photos of the packaging and product within the timeframe stated in our terms so we can assist.</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Payments &amp; security</h2>
                    <ul>
                        <li>Payments are processed through our checkout partners; <?php echo html_escape($this->app_name); ?> does not store your full card number on our servers.</li>
                        <li>Never share passwords, SMS OTPs, or payment codes with anyone claiming to be “support.” We will not ask for your full card details by email.</li>
                        <li>If you see a charge you do not recognise, contact your bank first, then email us with the date and amount so we can investigate.</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Account &amp; sign-in</h2>
                    <ul>
                        <li>Use the same email you registered with when contacting support about login issues.</li>
                        <li>If you forgot your password, use the password reset flow on the sign-in page (check spam folders for reset mail).</li>
                        <li>Keep your profile and shipping addresses up to date to avoid failed deliveries.</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Sellers &amp; shops</h2>
                    <ul>
                        <li>Independent sellers list their own products; questions about an item may be answered fastest via the seller where messaging is available.</li>
                        <li>For shop settings, listings, or payouts, signed-in sellers should use their dashboard and follow in-app or on-site prompts.</li>
                        <li>Marketplace support can help with platform issues; specific commercial terms may vary by seller and product category.</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Refunds &amp; disputes</h2>
                    <ul>
                        <li>Refund eligibility depends on the product type, seller policy, and applicable law—see our terms for the governing rules.</li>
                        <li>Open a support thread with your order number and a short explanation; attach photos if the item is wrong or defective.</li>
                        <li>We may mediate between buyer and seller; outcomes can take several business days when banks or couriers are involved.</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Mobile app</h2>
                    <p>
                        The Azura app connects to the same marketplace as this website. Install updates from the
                        <strong>Google Play</strong> or <strong>Apple App Store</strong> listing for the best performance and security fixes.
                    </p>
                    <p class="m-b-0">
                        If the app crashes or will not log in, note your phone model and OS version when you email support—that helps us reproduce the issue.
                    </p>
                </div>
            </div>

            <div class="col-12 m-b-30">
                <div class="page-text-content post-text-responsive">
                    <h2 class="h3">Frequently asked questions</h2>
                    <details class="m-b-15">
                        <summary><strong>I did not get a confirmation email</strong></summary>
                        <p class="m-t-10 m-b-0">Check spam and promotions folders. Verify the email on your account. If nothing arrives within a few hours, contact us with the approximate time of checkout and the payment reference if you have one.</p>
                    </details>
                    <details class="m-b-15">
                        <summary><strong>Can I change my shipping address after ordering?</strong></summary>
                        <p class="m-t-10 m-b-0">Address changes are only possible before the order is dispatched. Message us immediately with your order number and the new address; we will confirm if the change can still be applied.</p>
                    </details>
                    <details class="m-b-15">
                        <summary><strong>How do I report a suspicious message or listing?</strong></summary>
                        <p class="m-t-10 m-b-0">Forward screenshots and links to our support email. Do not pay outside the official checkout flow or share OTPs with third parties.</p>
                    </details>
                    <details class="m-b-15">
                        <summary><strong>Do you offer phone support in every country?</strong></summary>
                        <p class="m-t-10 m-b-0">Phone availability depends on the numbers published on this page. Email is available worldwide and is often the best channel for attachments and order references.</p>
                    </details>
                    <details class="m-b-0">
                        <summary><strong>Where can I read how you use my data?</strong></summary>
                        <p class="m-t-10 m-b-0">See our <a href="<?php echo lang_base_url(); ?>privacy-policy">Privacy Policy</a> for how we collect, use, and protect personal information.</p>
                    </details>
                </div>
            </div>

            <div class="col-12">
                <div class="page-text-content post-text-responsive text-muted small">
                    <p class="m-b-0">This support page is provided for customers and app store reviewers. Information may be updated from time to time without notice; the version on this site is the current reference.</p>
                </div>
            </div>
        </div>
    </div>
</div>
