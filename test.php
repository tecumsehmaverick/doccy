<?php

	require_once 'libs/doccy.php';

	$start = microtime(true);
	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	$tpl->parseString("First things first, you'll -- 'need a {code: {em: fresh -- copy}} of Symphony', so head to {a @href http://symphony-cms.com/downloads: the Symphony webite} then download and extract the latest release.");

	echo '<pre style="white-space: pre-wrap;">';

	foreach ($tpl->documentElement->childNodes as $node) {
		echo htmlentities($tpl->saveXML($node), ENT_NOQUOTES, 'UTF-8'), "\n";
	}

	printf(
		"\nExecuted in %.6f seconds using %.2fMB of memory.</pre>",
		microtime(true) - $start,
		memory_get_peak_usage() / 1024 / 1024
	);