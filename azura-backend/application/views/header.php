<?php defined('BASEPATH') OR exit('no direct script access allowed');?>
<!DOCTYPE html>
<!--[if lte IE 6]><html class="preIE7 preIE8 preIE9"><![endif]-->
<!--[if IE 7]><html class="preIE8 preIE9"><![endif]-->
<!--[if IE 8]><html class="preIE9"><![endif]-->
<!--[if gte IE 9]><!-->
<html lang="en"><!--<![endif]-->
<head>
    <!-- Page Title -->
    <title>RAHISI RECHARGE | Welcome</title>

    <!-- Metadata Tags -->
        <meta charset="UTF-8" http-equiv="Content-Type" content="text/html;" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="theme-color" content="#F6931E" />
        <meta name="apple-mobile-web-app-status-bar-style" content="#F6931E" />
        <meta name="keywords" content="food, order, online, thai, meal" />
        <meta property="og:type" content="website" />
        <meta name="description" content="Rahisi Recharge" />
        <meta name="author" content="Rahisi Recharge" />
    <!-- End of metadata Tags -->

    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('assets/img/favicon.png') ?>">

    <!-- Stylesheets -->
        <!-- Reset CSS -->
        <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/reset.css') ?>" />

        <!-- Vendor CSS -->
            <!-- Bootstrap -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <!-- Site CSS -->
        <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/style.css') ?>" />
        <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/index.css') ?>" />
    <!-- End of Stylesheets -->

    <!-- Header Javascript -->
        <!-- Modernizer Script -->
        <script src="<?= base_url('assets/lib/modernizr-3.6.0.min.js') ?>"></script>
    <!-- End of Header Javascript -->
</head>
<body class="overflow-x-hidden bg-wall-color position-relative d-flex">
     <!-- Header container -->
    <div class="container full-width no-margin no-padding body-container flex-fill d-flex flex-column">
        <header class="header overflow-y-visible">
            <div class="container header-container d-flex flex-row justify-content-end align-items-center overflow-y-visible">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php
                            $language = $this->session->userdata('site_lang');
                            $language = isset($language)? $language : "english";
                        ?>
                        <img src="<?= base_url('assets/img/flag-'.substr($language, 0, 2).'.png') ?>" class="rounded-5" width="32" height="32" />
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="setlang?language=swahili">
                            <img src="<?= base_url('assets/img/flag-sw.png') ?>" class="rounded-5" width="24" height="24" />
                            <span>&nbsp;<?= lang('swahiliText'); ?></span>
                        </a>
                        <a class="dropdown-item" href="setlang?language=english">
                            <img src="<?= base_url('assets/img/flag-en.png') ?>" class="rounded-5" width="24" height="24" />
                            <span>&nbsp;<?= lang('englishText'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <main class="content flex-fill pt-2 pb-4">
            <div class="container content-container px-4 pt-1 pb-4">
                <div class="container logo-container my-4">
                    <a href="<?= base_url()?>">
                        <img src="<?= base_url('assets/img/logo.png') ?>" width="96" height="96" class="" alt="Azura Mall" />
                    </a>
                </div>
    


