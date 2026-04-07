<?php defined('BASEPATH') or exit('no direct script access allowed'); ?>
<div class="container form-container my-4">
    <h5 class="my-3"><?= lang('payment_title'); ?>.</h5>
    <p class="mb-3"><?= lang('payment_instructions'); ?>.</p>
    <?php if ($this->session->flashdata('payment_error') !== null) { ?>
        <div class="alert alert-danger error-alert d-flex flex-row justify-content-between align-items-center" role="alert">
            <div class="flex-fill"><?= $this->session->flashdata('payment_error'); ?>.</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>
    <div>
        <ul class="nav nav-tabs" id="payment-form-tab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="mobile-payment-tab" data-bs-toggle="tab" data-bs-target="#mobile-payment-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true"><ion-icon name="phone-portrait-outline"></ion-icon> <?= lang('mobileMoneyPaymentsText'); ?></button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="card-payment-tab" data-bs-toggle="tab" data-bs-target="#card-payment-tab-pane" type="button" role="tab" aria-controls="card-payment-tab-pane" aria-selected="false"><ion-icon name="card-outline"></ion-icon> <?= lang('cardPaymentsText'); ?></button>
          </li>
        </ul>
        <div class="tab-content" id="payment-form-tab-content">
            <div class="tab-pane fade show active" id="mobile-payment-tab-pane" role="tabpanel" aria-labelledby="mobile-payment-tab" tabindex="0">
                <form class="form" method="POST" action="<?= site_url('pay') ?>" id="payment-form">
                    <input type="hidden" name="payment_methods" value="MOBILE" />
                    <input type="hidden" name="amount" value="<?= $this->session->flashdata('amount'); ?>" />
                    <input type="hidden" name="reference_id" value="<?= $this->session->flashdata('reference_id'); ?>" />
                    <input type="hidden" name="reference_number" value="<?= $this->session->flashdata('reference_number'); ?>" />
                    <input type="hidden" name="meter_number" value="<?= $this->session->flashdata('meter_number'); ?>" />
                    <div class="my-3">
                        <label for="phone" class="form-label"><?= lang('phoneNumberText'); ?></label>
                        <div class="input-group">
                            <div id="calling_code_wrapper">
                                <select class="form-select form-control rounded-5 px-3 m-0" aria-label="<?= lang('selectCallingCodeText'); ?>" name="calling_code" id="calling_code" required="true">
                                    <option value="255" <?php if ($this->session->flashdata('calling_code') === '255') echo 'selected'; ?>>+255</option>
                                    <!--<option value="254" <?php if ($this->session->flashdata('calling_code') === '254') echo 'selected'; ?>>+254</option>-->
                                    <!--<option value="256" <?php if ($this->session->flashdata('calling_code') === '256') echo 'selected'; ?>>+256</option>-->
                                </select>
                            </div>
                            <input type="number" placeholder="<?= lang('yourPhoneNumberText'); ?>" class="form-control rounded-5 px-3" id="phone" name="phone" value="<?= $this->session->flashdata('phone'); ?>" aria-describedby="phoneHelp" required="true" />
                        </div>
                    </div>
                    <div class="btn-container mt-3 pt-3 d-flex flex-column justify-content-center align-items-center">
                        <button type="submit" class="btn btn-primary rounded-5 px-4 mb-4">
                            <b><?= lang('continueText'); ?></b>
                        </button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade show" id="card-payment-tab-pane" role="tabpanel" aria-labelledby="card-payment-tab" tabindex="1">
                <form class="form" method="POST" action="<?= site_url('pay') ?>" id="payment-form">
                    <input type="hidden" name="payment_methods" value="CARD" />
                    <input type="hidden" name="amount" value="<?= $this->session->flashdata('amount'); ?>" />
                    <input type="hidden" name="reference_id" value="<?= $this->session->flashdata('reference_id'); ?>" />
                    <input type="hidden" name="reference_number" value="<?= $this->session->flashdata('reference_number'); ?>" />
                    <input type="hidden" name="meter_number" value="<?= $this->session->flashdata('meter_number'); ?>" />
                    <div class="my-3">
                        <label for="email" class="form-label"><?= lang('nameText'); ?></label>
                        <input type="text" placeholder="<?= lang('yourFullNameText'); ?>" class="form-control rounded-5 px-3" id="name" name="name" value="<?= $this->session->flashdata('name'); ?>" required="true" />
                    </div>
                    <div class="mb-3">
                        <div class="input-group row p-0 m-0">
                            <div class='col-6 col-sm-12 col-md-6 col-lg-6 ps-0 ms-0'>
                                <label for="country" class="form-label"><?= lang('countryText'); ?></label>
                                <div class="input-group">
                                    <select class="form-select form-control rounded-5 px-3" aria-label="<?= lang('selectCountryText'); ?>" name="country" id="country" required="true">
                                        <option value="tz" <?php if ($this->session->flashdata('country') === 'tz') echo 'selected'; ?>>Tanzania</option>
                                        <!--<option value="ke" <?php if ($this->session->flashdata('country') === 'ke') echo 'selected'; ?>>Kenya</option>-->
                                        <!--<option value="ug" <?php if ($this->session->flashdata('country') === 'ug') echo 'selected'; ?>>Uganda</option>-->
                                    </select>
                                </div>
                            </div>
                            <div class='col-6 col-sm-12 col-md-6 col-lg-6 pe-0 me-0'>
                                <label for="state_or_region" class="form-label"><?= lang('stateOrRegionText'); ?></label>
                                <div class="input-group">
                                    <input type="text" placeholder="<?= lang('yourStateOrRegionText'); ?>" class="form-control rounded-5 px-3 ms-1" id="state_or_region" name="state_or_region" value="<?= $this->session->flashdata('state_or_region'); ?>" required="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label"><?= lang('phoneNumberText'); ?></label>
                        <div class="input-group">
                            <div id="calling_code_wrapper">
                                <select class="form-select form-control rounded-5 px-3 m-0" aria-label="<?= lang('selectCallingCodeText'); ?>" name="calling_code" id="calling_code" required="true">
                                    <option value="255" <?php if ($this->session->flashdata('calling_code') === '255') echo 'selected'; ?>>+255</option>
                                    <!--<option value="254" <?php if ($this->session->flashdata('calling_code') === '254') echo 'selected'; ?>>+254</option>-->
                                    <!--<option value="256" <?php if ($this->session->flashdata('calling_code') === '256') echo 'selected'; ?>>+256</option>-->
                                </select>
                            </div>
                            <input type="number" placeholder="<?= lang('yourPhoneNumberText'); ?>" class="form-control rounded-5 px-3 me-0" id="phone" name="phone" value="<?= $this->session->flashdata('phone'); ?>" required="true" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= lang('emailText'); ?></label>
                        <input type="email" placeholder="<?= lang('yourEmailAddressText'); ?>" class="form-control rounded-5 px-3" id="email" name="email" value="<?= $this->session->flashdata('email'); ?>" required="true" />
                    </div>
                    <div class="mb-3">
                        <div class="input-group row p-0 m-0">
                            <div class='col-6 col-sm-12 col-md-6 col-lg-6 ps-0 ms-0'>
                                <label for="postcode_or_pobox" class="form-label"><?= lang('postalCodeOrPostalBoxText'); ?></label>
                                <div class="input-group">
                                    <input type="number" placeholder="<?= lang('yourPostalCodeOrPostalBoxText'); ?>" class="form-control rounded-5 px-3 ms-1" id="postcode_or_pobox" name="postcode_or_pobox" value="<?= $this->session->flashdata('postcode_or_pobox'); ?>" required="true" />
                                </div>
                            </div>
                            <div class='col-6 col-sm-12 col-md-6 col-lg-6 pe-0 me-0'>
                                <label for="city" class="form-label"><?= lang('cityOrTownText'); ?></label>
                                <div class="input-group">
                                    <input type="text" placeholder="<?= lang('postalCodeOrPostalBoxCityText'); ?>" class="form-control rounded-5 px-3 ms-1" id="city" name="city" value="<?= $this->session->flashdata('city'); ?>" required="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label"><?= lang('addressText'); ?></label>
                        <input type="text" placeholder="<?= lang('yourAddressText'); ?>" class="form-control rounded-5 px-3" id="address" name="address" value="<?= $this->session->flashdata('address'); ?>" required="true" />
                    </div>
                    <div class="btn-container mt-3 pt-3 d-flex flex-column justify-content-center align-items-center">
                        <button type="submit" class="btn btn-primary rounded-5 px-4 mb-4">
                            <b><?= lang('continueText'); ?></b>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>