<?php

/**
 * Gutenberg Templates plugin's main bootstrap file.
 *
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
require_once GBT_PATH . '/inc/autoload.php';
$gbt = new \Gutenberg_Templates\Controllers\Gutenberg_Templates();
$gbt->initialize();
