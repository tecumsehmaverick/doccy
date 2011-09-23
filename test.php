<?php

	require_once 'libs/doccy.php';

	$start = microtime(true);
	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	$tpl->parseURI('readme.dcy');

	echo '<pre style="white-space: pre-wrap;">';

	foreach ($tpl->documentElement->childNodes as $node) {
		echo htmlentities($tpl->saveXML($node), ENT_NOQUOTES, 'UTF-8');
	}

	printf(
		"\nExecuted in %.6f seconds using %.2fMB of memory.</pre>",
		microtime(true) - $start,
		memory_get_peak_usage() / 1024 / 1024
	);