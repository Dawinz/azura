<?php $clr =$this->general_settings->site_color;?>
<!-- premium-elegance-v1 -->
<style>body {<?php echo $this->fonts->site_font_family; ?>}
<?php if(!empty($index_banners_array)):foreach ($index_banners_array as $banner_set):foreach ($banner_set as $banner):?>.index_bn_<?=$banner->id;?> {-ms-flex: 0 0 <?=$banner->banner_width;?>%;flex: 0 0 <?=$banner->banner_width;?>%;max-width: <?=$banner->banner_width;?>%;}  <?php endforeach; endforeach; endif; ?>
    a:active,a:focus,a:hover{color:<?= $clr; ?>}.btn-custom{background-color:<?= $clr; ?>;border-color:<?= $clr; ?>}.btn-block{background-color:<?= $clr; ?>}.btn-outline{border:1px solid <?= $clr; ?>;color:<?= $clr; ?>}.btn-outline:hover{background-color:<?= $clr; ?>!important}.btn-filter-products-mobile{border:1px solid <?= $clr; ?>;background-color:<?= $clr; ?>}.form-control:focus{border-color:<?= $clr; ?>}.link{color:<?= $clr; ?>!important}.link-color{color:<?= $clr; ?>}.top-search-bar .btn-search{background-color:<?= $clr; ?>}.nav-top .nav-top-right .nav li a:active,.nav-top .nav-top-right .nav li a:focus,.nav-top .nav-top-right .nav li a:hover{color:<?= $clr; ?>}.nav-top .nav-top-right .nav li .btn-sell-now{background-color:<?= $clr; ?>!important}.nav-main .navbar>.navbar-nav>.nav-item:hover .nav-link:before{background-color:<?= $clr; ?>}.li-favorites a i{color:<?= $clr; ?>}.product-share ul li a:hover{color:<?= $clr; ?>}.pricing-card:after{background-color:<?= $clr; ?>}.selected-card{-webkit-box-shadow:0 3px 0 0 <?= $clr; ?>;box-shadow:0 3px 0 0 <?= $clr; ?>}.selected-card .btn-pricing-button{background-color:<?= $clr; ?>}.profile-buttons .social ul li a:hover{background-color:<?= $clr; ?>;border-color:<?= $clr; ?>}.btn-product-promote{background-color:<?= $clr; ?>}.contact-social ul li a:hover{background-color:<?= $clr; ?>;border-color:<?= $clr; ?>}.price-slider .ui-slider-horizontal .ui-slider-handle{background:<?= $clr; ?>}.price-slider .ui-slider-range{background:<?= $clr; ?>}.p-social-media a:hover{color:<?= $clr; ?>}.blog-content .blog-categories .active a{background-color:<?= $clr; ?>}.nav-payout-accounts .active,.nav-payout-accounts .show>.nav-link{background-color:<?= $clr; ?>!important}.pagination .active a{border:1px solid <?= $clr; ?>!important;background-color:<?= $clr; ?>!important}.pagination li a:active,.pagination li a:focus,.pagination li a:hover{background-color:<?= $clr; ?>;border:1px solid <?= $clr; ?>}.spinner>div{background-color:<?= $clr; ?>}::selection{background:<?= $clr; ?>!important}::-moz-selection{background:<?= $clr; ?>!important}.cookies-warning a{color:<?= $clr; ?>}.custom-checkbox .custom-control-input:checked~.custom-control-label::before{background-color:<?= $clr; ?>}.custom-control-input:checked~.custom-control-label::before{border-color:<?= $clr; ?>;background-color:<?= $clr; ?>}.custom-control-variation .custom-control-input:checked~.custom-control-label{border-color:<?= $clr; ?>!important}.btn-wishlist .icon-heart{color:<?= $clr; ?>}.product-item-options .item-option .icon-heart{color:<?= $clr; ?>}.mobile-language-options li .selected,.mobile-language-options li a:hover{color:<?= $clr; ?>;border:1px solid <?= $clr; ?>}.mega-menu .link-view-all, .link-add-new-shipping-option{color:<?= $clr; ?>!important;}.mega-menu .menu-subcategories ul li .link-view-all:hover{border-color:<?= $clr; ?>!important}.custom-select:focus{border-color:<?= $clr; ?>}
    .featured-categories .card{background-size:cover;background-position:center;min-height:260px;border-radius:14px;overflow:hidden;box-shadow:0 10px 28px rgba(17,24,39,.12)}
    .featured-categories .card .caption{background:rgba(0,0,0,.55);padding:8px 12px;border-radius:8px;display:inline-block}
    .section{margin-bottom:32px}
    .section .title{font-size:1.32rem;font-weight:700;letter-spacing:.2px;margin-bottom:4px}
    .section .title-exp{color:#6b7280;font-size:.93rem;margin-bottom:14px}
    .row.row-product{margin-left:-9px;margin-right:-9px}
    .row.row-product .col-product{padding-left:9px;padding-right:9px;margin-bottom:18px}
    .product-item{height:100%;background:#fff;border:1px solid #eef1f5;border-radius:14px;padding:10px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
    .product-item:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(15,23,42,.12);border-color:#e5e7eb}
    .img-product-container{border-radius:10px;overflow:hidden}
    .img-product-container .img-product{width:100%;height:220px;object-fit:cover}
    .product-title{line-height:1.35;min-height:40px;margin-bottom:4px}
    .product-title a{font-size:.96rem;font-weight:600;color:#111827}
    .product-user a{font-size:.84rem;color:#6b7280}
    .item-meta{padding-top:4px}
    .btn,.btn-md{border-radius:10px}
    .btn-sell-now{border-radius:10px!important;padding:.5rem .95rem!important}
    .top-search-bar .input-search{border-radius:999px;border:1px solid #e5e7eb}
    .top-search-bar .btn-search{border-radius:999px}
    #header .main-menu{box-shadow:0 1px 0 rgba(17,24,39,.06)}
    #footer{margin-top:24px}
    #footer .footer-top{padding-top:34px;padding-bottom:26px}
    #footer .footer-title{font-size:.95rem;font-weight:700;letter-spacing:.2px}
    #footer .copyright{color:#6b7280}
    @media (max-width:991px){.featured-categories .card{min-height:190px}.img-product-container .img-product{height:170px}.section{margin-bottom:22px}.product-item{padding:8px}}
    @media (max-width:575px){.featured-categories .card{min-height:150px}.img-product-container .img-product{height:150px}.row.row-product .col-product{margin-bottom:12px}.section .title{font-size:1.15rem}}
    .azura-app-download-pill{display:inline-flex;align-items:center;padding:.42rem .85rem;border-radius:999px;font-size:.88rem;font-weight:600;color:#fff!important;background:linear-gradient(135deg,<?= $clr; ?> 0%,#1e3a5f 100%);box-shadow:0 4px 14px rgba(17,24,39,.18);text-decoration:none!important;margin-right:.35rem;transition:transform .15s ease,box-shadow .15s ease}
    .azura-app-download-pill:hover,.azura-app-download-pill:focus{color:#fff!important;transform:translateY(-1px);box-shadow:0 6px 18px rgba(17,24,39,.22)}
    .azura-app-pill-inner{display:inline-flex;align-items:center;gap:6px}
    .top-bar .navbar-nav .azura-app-download-pill.nav-link{padding:.34rem .8rem!important;margin-top:2px;color:#fff!important;border:none}
    .top-bar .azura-topbar-app-text{white-space:nowrap}
    @media (max-width:575px){.top-bar .azura-topbar-app-text{display:none}.top-bar .navbar-nav .azura-app-download-pill.nav-link{padding:.34rem .55rem!important}}
    .nav-mobile-header .mobile-app-banner{display:flex;align-items:center;justify-content:center;margin-right:8px}
    .nav-mobile-header .mobile-app-banner-link{color:#111827;font-size:1.15rem;padding:6px;border-radius:10px;background:#f3f4f6;line-height:1}
    .nav-mobile-header .mobile-app-banner-link:hover{color:<?= $clr; ?>}
    .azura-app-modal-dialog{max-width:400px}
    .azura-app-modal-content{border:none;border-radius:18px;box-shadow:0 24px 48px rgba(15,23,42,.2)}
    .azura-app-modal-body{padding:2rem 1.75rem 1.75rem;position:relative;text-align:center}
    .azura-app-modal-close{position:absolute;top:12px;right:14px;opacity:.55}
    .azura-app-modal-icon-wrap{margin-bottom:1rem}
    .azura-app-modal-icon{display:inline-flex;width:56px;height:56px;border-radius:16px;align-items:center;justify-content:center;background:linear-gradient(135deg,<?= $clr; ?> 0%,#1e3a5f 100%);color:#fff;font-size:1.35rem}
    .azura-app-modal-title{font-size:1.25rem;font-weight:700;margin-bottom:.65rem}
    .azura-app-modal-text{color:#4b5563;font-size:.95rem;line-height:1.5;margin-bottom:1.25rem}
    .azura-app-download-btn{border-radius:12px!important;font-weight:600;padding:.65rem 1rem!important;display:inline-flex;align-items:center;justify-content:center;gap:8px}
    .azura-app-modal-hint{font-size:.8rem;margin-top:.75rem;margin-bottom:0;line-height:1.4}
</style>
<script>var mds_config = {base_url: "<?= base_url(); ?>", lang_base_url: "<?= lang_base_url(); ?>", sys_lang_id: "<?= $this->selected_lang->id; ?>", thousands_separator: "<?= $this->thousands_separator; ?>", csfr_token_name: "<?= $this->security->get_csrf_token_name(); ?>", csfr_cookie_name: "<?= $this->config->item('csrf_cookie_name'); ?>", txt_all: "<?= trans("all"); ?>", txt_no_results_found: "<?= trans("no_results_found"); ?>", sweetalert_ok: "<?= trans("ok"); ?>", sweetalert_cancel: "<?= trans("cancel"); ?>", msg_accept_terms: "<?= trans("msg_accept_terms"); ?>", cart_route: "<?= !empty($this->routes) && !empty($this->routes->cart) ? $this->routes->cart : ''; ?>", slider_fade_effect: "<?= ($this->general_settings->slider_effect == "fade") ? 1 : 0; ?>", is_recaptcha_enabled: "<?= !empty($recaptcha_status) && $recaptcha_status == true ? "true" : "false" ?>", rtl: <?= $this->rtl == "true" ? true : "false" ?>};if(mds_config.rtl==1){mds_config.rtl=true;}</script>