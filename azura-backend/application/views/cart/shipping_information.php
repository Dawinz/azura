<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Wrapper -->
<div id="wrapper">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="shopping-cart shopping-cart-shipping">
                    <div class="row">
                        <div class="col-sm-12 col-lg-8">
                            <div class="left">
                                <h1 class="cart-section-title"><?php echo trans("checkout"); ?></h1>
                                
                                <!-- Shipping Information Section (unchanged from previous) -->
                                <div class="tab-checkout tab-checkout-open m-t-0">
                                    <h2 class="title">1.&nbsp;&nbsp;<?php echo trans("shipping_information"); ?></h2>
                                    <?php $this->load->view('partials/_messages'); ?>

                                    <?php if (empty($shipping_addresses)): ?>
                                        <p class="text-muted"><?= trans("not_added_shipping_address"); ?></p>
                                        <p>
                                            <a href="javascript:void(0)" class="text-info link-add-new-shipping-option" data-toggle="modal" data-target="#modalAddAddress">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                                </svg>
                                                <?= trans("add_new_address"); ?>
                                            </a>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-right">
                                            <a href="javascript:void(0)" class="text-info link-add-new-shipping-option" data-toggle="modal" data-target="#modalAddAddress">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                                </svg>
                                                <?= trans("add_new_address"); ?>
                                            </a>
                                        </p>
                                        <?php echo form_open("shipping-post", ['id' => 'form_validate']); ?>
                                        <p class="text-shipping-address"><?= trans("shipping_address"); ?></p>
                                        <div class="row">
                                            <?php if (!empty($shipping_addresses)):
                                                $i = 0;
                                                foreach ($shipping_addresses as $address):
                                                    $country = get_country($address->country_id);
                                                    $state = get_state($address->state_id); ?>
                                                    <div class="col-12 m-b-10">
                                                        <div class="shipping-address-box shipping-address-box-cart">
                                                            <div class="address-left">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" class="custom-control-input" id="option_shipping_address_<?= $address->id; ?>" name="shipping_address_id" value="<?= $address->id; ?>"
                                                                        <?= $selected_shipping_address_id == $address->id ? 'checked' : ''; ?> onchange="get_shipping_methods_by_location('<?= $address->state_id; ?>');" required>
                                                                    <label class="custom-control-label" for="option_shipping_address_<?= $address->id; ?>">
                                                                        <strong class="m-b-5"><?= html_escape($address->title); ?></strong>
                                                                        <p>
                                                                            <?= html_escape($address->address); ?>&nbsp;<?= html_escape($address->zip_code); ?>
                                                                            <?php if (!empty($address->city)):
                                                                                echo html_escape($address->city) . "/";
                                                                            endif;
                                                                            if (!empty($state->name)):
                                                                                echo html_escape($state->name) . "/";
                                                                            endif;
                                                                            if (!empty($country->name)):
                                                                                echo html_escape($country->name);
                                                                            endif; ?>
                                                                        </p>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="address-right">
                                                                <div class="dropdown dropdown-shipping-options">
                                                                    <button class="btn" type="button" data-toggle="dropdown">
                                                                        <svg width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                                                            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                                        </svg>
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        <a href="javascript:void(0)" class="dropdown-item" data-toggle="modal" data-target="#modalAddress<?= $address->id; ?>"><?= trans("edit"); ?></a>
                                                                        <a href="javascript:void(0)" class="dropdown-item" onclick='delete_shipping_address("<?= $address->id; ?>","<?= trans("confirm_delete"); ?>");'><?= trans("delete"); ?></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 m-t-10">
                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" name="use_same_address_for_billing" value="1" id="use_same_address_for_billing" <?= $selected_same_address_for_billing == 1 ? 'checked' : ''; ?>>
                                                        <label for="use_same_address_for_billing" class="custom-control-label"><?php echo trans("use_same_address_for_billing"); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cart-form-billing-address" <?= empty($selected_same_address_for_billing) ? "style='display:block;'" : ""; ?>>
                                            <p class="text-shipping-address"><?= trans("billing_address"); ?></p>
                                            <div class="row">
                                                <?php if (!empty($shipping_addresses)):
                                                    foreach ($shipping_addresses as $address):
                                                        $country = get_country($address->country_id);
                                                        $state = get_state($address->state_id); ?>
                                                        <div class="col-12 m-b-10">
                                                            <div class="shipping-address-box shipping-address-box-cart">
                                                                <div class="address-left">
                                                                    <div class="custom-control custom-radio">
                                                                        <input type="radio" class="custom-control-input" id="option_billing_address_<?= $address->id; ?>" name="billing_address_id" value="<?= $address->id; ?>" <?= $selected_billing_address_id == $address->id ? 'checked' : ''; ?> required>
                                                                        <label class="custom-control-label" for="option_billing_address_<?= $address->id; ?>">
                                                                            <strong class="m-b-5"><?= html_escape($address->title); ?></strong>
                                                                            <p>
                                                                                <?= html_escape($address->address); ?>&nbsp;<?= html_escape($address->zip_code); ?>
                                                                                <?php if (!empty($address->city)):
                                                                                    echo html_escape($address->city) . "/";
                                                                                endif;
                                                                                if (!empty($state->name)):
                                                                                    echo html_escape($state->name) . "/";
                                                                                endif;
                                                                                if (!empty($country->name)):
                                                                                    echo html_escape($country->name);
                                                                                endif; ?>
                                                                            </p>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="address-right">
                                                                    <div class="dropdown dropdown-shipping-options">
                                                                        <button class="btn" type="button" data-toggle="dropdown">
                                                                            <svg width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                                                                <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                                            </svg>
                                                                        </button>
                                                                        <div class="dropdown-menu">
                                                                            <a href="javascript:void(0)" class="dropdown-item" data-toggle="modal" data-target="#modalAddress<?= $address->id; ?>"><?= trans("edit"); ?></a>
                                                                            <a href="javascript:void(0)" class="dropdown-item" onclick='delete_shipping_address("<?= $address->id; ?>","<?= trans("confirm_delete"); ?>");'><?= trans("delete"); ?></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php $i++;
                                                    endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div id="cart_shipping_methods_container">
                                            <?php $this->load->view("cart/_shipping_methods"); ?>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="cart-shipping-loader">
                                                    <div class="spinner">
                                                        <div class="bounce1"></div>
                                                        <div class="bounce2"></div>
                                                        <div class="bounce3"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php echo form_close(); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Payment Method Section -->
                                <div class="tab-checkout tab-checkout-open">
                                    <h2 class="title">2.&nbsp;&nbsp;<?php echo trans("payment_method"); ?></h2>
                                    <div class="payment-methods-container">
                                        <?php echo form_open('selcom-payment-post', ['id' => 'payment_method_form']); ?>
                                        <input type="hidden" name="mds_payment_type" value="sale">
                                        <?php
                                        $user = $this->auth_user;
                                        $user_phone = !empty($user->phone_number) ? $user->phone_number : '';
                                        ?>
                                        
                                        <!-- Payment Method Selection -->
                                        <div class="payment-method-options">
                                            <div class="payment-method-option active" data-method="MOBILE">
                                                <input type="radio" id="payment_selcom" name="payment_methods" value="MOBILE" checked>
                                                <label for="payment_selcom">
                                                    <div class="payment-icon">
                                                        <i class="fas fa-mobile-alt"></i>
                                                    </div>
                                                    <div class="payment-details">
                                                        <h4>Selcom Mobile Payment</h4>
                                                        <p>Pay via mobile money (M-Pesa, Tigo Pesa, Airtel Money)</p>
                                                    </div>
                                                    <div class="payment-check">
                                                        <i class="fas fa-check"></i>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method-option" data-method="CARD">
                                                <input type="radio" id="payment_card" name="payment_methods" value="CARD">
                                                <label for="payment_card">
                                                    <div class="payment-icon">
                                                        <i class="far fa-credit-card"></i>
                                                    </div>
                                                    <div class="payment-details">
                                                        <h4>Credit/Debit Card</h4>
                                                        <p>Pay with Visa, Mastercard or American Express</p>
                                                    </div>
                                                    <div class="payment-check">
                                                        <i class="fas fa-check"></i>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Mobile Payment Fields -->
                                        <div id="mobile_payment_fields" class="payment-fields">
                                            <div class="form-group">
                                                <label><?= trans("phone_number"); ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend" style="width:30%;">
                                                        <select name="calling_code" class="form-control" style="border-top-right-radius:0;border-bottom-right-radius:0;">
                                                            <option value="255" <?= strpos($user_phone, '255') === 0 ? 'selected' : ''; ?>>Tanzania (+255)</option>
                                                            <option value="254" <?= strpos($user_phone, '254') === 0 ? 'selected' : ''; ?>>Kenya (+254)</option>
                                                            <option value="256" <?= strpos($user_phone, '256') === 0 ? 'selected' : ''; ?>>Uganda (+256)</option>
                                                            <option value="250" <?= strpos($user_phone, '250') === 0 ? 'selected' : ''; ?>>Rwanda (+250)</option>
                                                            <option value="257" <?= strpos($user_phone, '257') === 0 ? 'selected' : ''; ?>>Burundi (+257)</option>
                                                        </select>
                                                    </div>
                                                    <input type="text" name="phone" class="form-control" style="border-top-left-radius:0;border-bottom-left-radius:0;" 
                                                        value="<?= preg_replace('/^\+?\d+/', '', $user_phone); ?>" placeholder="712345678" required>
                                                </div>
                                                <small class="form-text text-muted">Enter your mobile money registered number</small>
                                            </div>
                                        </div>

                                        <!-- Card Payment Fields (hidden by default) -->
                                        <div id="card_payment_fields" class="payment-fields" style="display:none;">
                                            <!-- Card fields here -->
                                        </div>

                                        <!-- Checkout Button -->
                                        <div class="checkout-button-container">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <span class="button-content">
                                                    <i class="fas fa-lock"></i>
                                                    <span>Pay with Selcom</span>
                                                </span>
                                            </button>
                                        </div>
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <?php $this->load->view("cart/_order_summary"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS -->
<style>
    /* Payment Method Options */
    .payment-method-options {
        margin-bottom: 1.5rem;
    }
    
    .payment-method-option {
        margin-bottom: 0.75rem;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
        position: relative;
    }
    
    .payment-method-option.active {
        border-color: #4e73df;
        background-color: rgba(78, 115, 223, 0.05);
    }
    
    .payment-method-option:hover {
        border-color: #4e73df;
    }
    
    .payment-method-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .payment-method-option label {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        cursor: pointer;
        width: 100%;
    }
    
    .payment-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: #4e73df;
        font-size: 1.1rem;
    }
    
    .payment-details {
        flex: 1;
    }
    
    .payment-details h4 {
        margin-bottom: 0.25rem;
        color: #2e3a59;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .payment-details p {
        color: #6c757d;
        margin-bottom: 0;
        font-size: 0.85rem;
    }
    
    .payment-check {
        color: #4e73df;
        font-size: 1rem;
        opacity: 0;
        transition: opacity 0.2s;
        margin-left: 1rem;
    }
    
    .payment-method-option.active .payment-check {
        opacity: 1;
    }
    
    /* Payment Fields */
    .payment-fields {
        padding: 1.25rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    /* Checkout Button */
    .checkout-button-container {
        margin-top: 1.5rem;
    }
    
    .btn-primary {
        padding: 0.75rem;
        font-size: 1rem;
        font-weight: 500;
    }
    
    /* Phone Input Group */
    .input-group-prepend {
        width: 30%;
    }
    
    .input-group-prepend select {
        height: 100%;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .payment-method-option {
            padding: 0.75rem;
        }
        
        .payment-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
            margin-right: 0.75rem;
        }
        
        .payment-details h4 {
            font-size: 0.95rem;
        }
    }
    
    @media (max-width: 576px) {
        .input-group-prepend {
            width: 40%;
        }
    }
</style>

<!-- JavaScript: no extra jQuery — footer already loads jQuery; use DOM APIs so this runs if footer order changes -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.payment-method-option').forEach(function (opt) {
        opt.addEventListener('click', function () {
            document.querySelectorAll('.payment-method-option').forEach(function (o) { o.classList.remove('active'); });
            opt.classList.add('active');
            var method = opt.getAttribute('data-method');
            var mobile = document.getElementById('mobile_payment_fields');
            var card = document.getElementById('card_payment_fields');
            var label = document.querySelector('.checkout-button-container .btn-primary .button-content span:last-child');
            if (method === 'MOBILE') {
                if (card) { card.style.display = 'none'; }
                if (mobile) { mobile.style.display = ''; }
                if (label) { label.textContent = 'Pay with Selcom'; }
            } else if (method === 'CARD') {
                if (mobile) { mobile.style.display = 'none'; }
                if (card) { card.style.display = ''; }
                if (label) { label.textContent = 'Pay with Card'; }
            }
        });
    });
});
</script>
