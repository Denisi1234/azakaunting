<?php
/**
 * @var object          $user_info
 * @var array           $allowed_modules
 * @var IncomingRequest $request
 * @var array           $config
 */

use CodeIgniter\HTTP\IncomingRequest;
use Config\Services;

$request = Services::request();
?>

<!doctype html>
<html lang="<?= current_language_code() ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= base_url() ?>">
    <title><?= esc($config['company']) . ' | Aza Akaunting ' . esc(config('App')->application_version) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <?php $theme = (empty($config['theme']) ? 'flatly' : esc($config['theme'])); ?>
    <link rel="stylesheet" href="resources/bootswatch/<?= "{$theme}" ?>/bootstrap.min.css">

    <?php if (ENVIRONMENT === 'development' || get_cookie('debug') === 'true' || $request->getGet('debug') === 'true') : ?>
        <!-- inject:debug:css -->
        <link rel="stylesheet" href="resources/css/jquery-ui-49d1c743e3.css">
        <link rel="stylesheet" href="resources/css/bootstrap-dialog-1716ef6e7c.css">
        <link rel="stylesheet" href="resources/css/jasny-bootstrap-40bf85f3ed.css">
        <link rel="stylesheet" href="resources/css/bootstrap-datetimepicker-66374fba71.css">
        <link rel="stylesheet" href="resources/css/daterangepicker-85523b7dfe.css">
        <link rel="stylesheet" href="resources/css/bootstrap-tagsinput-5a6d46a06c.css">
        <link rel="stylesheet" href="resources/css/bootstrap-select-66d5473b84.css">
        <link rel="stylesheet" href="resources/css/bootstrap-table-9415887f7f.css">
        <link rel="stylesheet" href="resources/css/bootstrap-table-sticky-header-07d65e7533.css">
        <link rel="stylesheet" href="resources/css/bootstrap-toggle-e12db6c1f3.css">
        <link rel="stylesheet" href="resources/css/chartist-c19aedb81a.css">
        <link rel="stylesheet" href="resources/css/chartist-plugin-tooltip-2e0ec92e60.css">
        <link rel="stylesheet" href="resources/css/bootstrap-292fc0ad3b.autocomplete.css">
        <link rel="stylesheet" href="resources/css/invoice-918994ec61.css">
        <link rel="stylesheet" href="resources/css/ospos-a03edb2c1c.css">
        <link rel="stylesheet" href="resources/css/ospos_print-4840b333ff.css">
        <link rel="stylesheet" href="resources/css/popupbox-7b616030b0.css">
        <link rel="stylesheet" href="resources/css/receipt-a171207d8e.css">
        <link rel="stylesheet" href="resources/css/register-276f323f7e.css">
        <link rel="stylesheet" href="resources/css/reports-407b727797.css">
        <!-- endinject -->
        <!-- inject:debug:js -->
        <script src="resources/js/jquery-12e87d2f3a.js"></script>
        <script src="resources/js/jquery-ui-c0267985b7.js"></script>
        <script src="resources/js/bootstrap-894d79839f.js"></script>
        <script src="resources/js/bootstrap-table-d4bc308c7c.js"></script>
        <script src="resources/js/bootstrap-table-export-9bab513c39.js"></script>
        <script src="resources/js/bootstrap-table-mobile-e5b76c7e5a.js"></script>
        <script src="resources/js/bootstrap-table-sticky-header-b611d1fab9.js"></script>
        <script src="resources/js/moment-ad3346f890.min.js"></script>
        <script src="resources/js/jquery-4fa896f615.form.js"></script>
        <script src="resources/js/jquery-8416e49527.validate.js"></script>
        <script src="resources/js/bootstrap-dialog-27123abb65.js"></script>
        <script src="resources/js/jasny-bootstrap-7c6d7b8adf.js"></script>
        <script src="resources/js/bootstrap-select-b01896a67b.js"></script>
        <script src="resources/js/bootstrap-toggle-1c7a19a049.js"></script>
        <script src="resources/js/bootstrap-notify-376bc6eb87.js"></script>
        <script src="resources/js/bootstrap-datetimepicker-25e39b7ef8.js"></script>
        <script src="resources/js/FileSaver-e73b1946e8.js"></script>
        <script src="resources/js/html2canvas-e1d3a8d7cd.js"></script>
        <script src="resources/js/jspdf-bbbebb610c.umd.js"></script>
        <script src="resources/js/jspdf-6221cbe80a.plugin.autotable.js"></script>
        <script src="resources/js/purify-74ff140709.js"></script>
        <script src="resources/js/xlsx-6b3130af1c.full.min.js"></script>
        <script src="resources/js/tableExport-3d506dfa61.min.js"></script>
        <script src="resources/js/chartist-8a7ecb4445.js"></script>
        <script src="resources/js/chartist-plugin-axistitle-80a1198058.js"></script>
        <script src="resources/js/chartist-plugin-barlabels-4165273742.js"></script>
        <script src="resources/js/chartist-plugin-pointlabels-0a1ab6aa4e.js"></script>
        <script src="resources/js/chartist-plugin-tooltip-116cb48831.js"></script>
        <script src="resources/js/daterangepicker-048c56a690.js"></script>
        <script src="resources/js/imgpreview-62e42c15a0.full.jquery.js"></script>
        <script src="resources/js/manage_tables-bd08c5b199.js"></script>
        <script src="resources/js/nominatim-becb3f84b2.autocomplete.js"></script>
        <!-- endinject -->
    <?php else : ?>
        <!-- inject:prod:css -->
        <link rel="stylesheet" href="resources/opensourcepos-5f6af4c5ab.min.css">
        <!-- endinject -->

        <!-- Tweaks to the UI for a particular theme should drop here  -->
        <?php if ($config['theme'] !== 'flatly' && file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/css/' . esc($config['theme']) . '.css')) { ?>
            <link rel="stylesheet" href="<?= 'css/' . esc($config['theme']) . '.css' ?>">
        <?php } ?>
        <!-- inject:prod:js -->
        <script src="resources/jquery-2c872dbe60.min.js"></script>
        <script src="resources/opensourcepos-6802c36072.min.js"></script>
        <!-- endinject -->
    <?php endif; ?>

    <?= view('partial/header_js') ?>
    <?= view('partial/lang_lines') ?>

    <style>
        html {
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="topbar">
            <div class="container-fluid">
                <div class="navbar-left">
                    <div id="liveclock"><?= date($config['dateformat'] . ' ' . $config['timeformat']) ?></div>
                </div>

<div class="navbar-right" style="margin: 0;">
                    <div class="pull-right" style="display: inline-block; margin-right: 10px;">
                        <?= view('partial/notifications') ?>
                    </div>
                    <div class="pull-right" style="display: inline-block; margin-right: 10px;">
                        <?= view('partial/bookmarks') ?>
                    </div>
                    <span>&nbsp;|&nbsp;</span>
                    <?= anchor("home/changePassword/{$user_info->person_id}", "{$user_info->first_name} {$user_info->last_name}", ['class' => 'modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Employees.change_password')]) ?>
                    <span>&nbsp;|&nbsp;</span>
                    <?= anchor('home/logout', lang('Login.logout')) ?>
                </div>

                <div class="navbar-center" style="text-align: center;">
                    <strong><?= esc($config['company']) ?></strong>
                </div>
            </div>
        </div>

        <div class="ospos-layout">
            <div class="ospos-drawer-backdrop" id="ospos-drawer-backdrop"></div>
            <div class="navbar navbar-default ospos-sidebar" role="navigation">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle modern-hamburger-btn" id="mobile-hamburger-btn" aria-label="Open navigation menu" aria-controls="ospos-sidebar-collapse" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar bar-top"></span>
                        <span class="icon-bar bar-mid"></span>
                        <span class="icon-bar bar-bot"></span>
                    </button>

                    <a class="navbar-brand" href="<?= site_url() ?>">Aza Akaunting</a>
                </div>

                <div class="navbar-collapse collapse" id="ospos-sidebar-collapse" aria-hidden="true">
                    <div class="drawer-header visible-xs">
                        <div class="drawer-user-info">
                            <div class="drawer-avatar">
                                <?= strtoupper(substr($user_info->first_name ?? 'A', 0, 1) . substr($user_info->last_name ?? 'D', 0, 1)) ?>
                            </div>
                            <div class="drawer-user-meta">
                                <span class="drawer-user-name"><?= esc(($user_info->first_name ?? '') . ' ' . ($user_info->last_name ?? '')) ?></span>
                                <span class="drawer-company-name"><?= esc($config['company']) ?></span>
                            </div>
                        </div>
                        <button type="button" class="drawer-close-btn" aria-label="Close menu">&times;</button>
                    </div>

                    <div class="drawer-nav-wrapper">
                        <ul class="nav navbar-nav ospos-sidebar-nav">
                            <?php $currentSegment = $request->getUri()->getSegment(1); ?>
                            <li class="<?= ($currentSegment === '' || $currentSegment === 'home') ? 'active' : '' ?>">
                                <a href="<?= base_url('home') ?>" title="Dashboard" class="menu-icon">
                                    <span class="menu-icon-box">
                                        <img src="<?= base_url('images/menubar/home.svg') ?>" style="border: none;" alt="Dashboard Icon">
                                    </span>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <?php foreach ($allowed_modules as $module): ?>
                                <li class="<?= $module->module_id === $currentSegment ? 'active' : '' ?>">
                                    <a href="<?= base_url($module->module_id) ?>" title="<?= lang("Module.{$module->module_id}") ?>" class="menu-icon">
                                        <span class="menu-icon-box">
                                            <img src="<?= base_url("images/menubar/{$module->module_id}.svg") ?>" style="border: none;" alt="Module Icon">
                                        </span>
                                        <span><?= lang('Module.' . $module->module_id) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="drawer-footer visible-xs">
                        <?= anchor("home/changePassword/{$user_info->person_id}", '<span class="glyphicon glyphicon-lock"></span> ' . lang('Employees.change_password'), ['class' => 'drawer-footer-link modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Employees.change_password')]) ?>
                        <?= anchor('home/logout', '<span class="glyphicon glyphicon-log-out"></span> ' . lang('Login.logout'), ['class' => 'drawer-footer-link drawer-logout-link']) ?>
                    </div>
                </div>
            </div>

            <div class="ospos-main">
                <div class="container mobile-page-gutter">
