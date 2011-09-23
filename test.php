<?php

	require_once 'libs/doccy.php';

	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	$tpl->parseString("First things first, you'll need a fresh copy of Symphony, so head to {a @href http://symphony-cms.com/downloads: the Symphony webite} then download and extract the latest release.");

	echo '<pre style="white-space: pre-wrap;">';

	foreach ($tpl->documentElement->childNodes as $node) {
		echo htmlentities($tpl->saveXML($node), ENT_NOQUOTES, 'UTF-8'), "\n";
	}

?>