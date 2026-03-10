<?php
require_once 'config.php';
require_once 'auth_check.php';

// Load XML and XSLT
$xml = new DOMDocument();
$xml->load('products.xml');

$xsl = new DOMDocument();
$xsl->load('products.xsl');

// Transform
$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);

echo $proc->transformToXML($xml);
?>
