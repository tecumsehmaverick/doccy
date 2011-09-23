<?php

	require_once 'libs/doccy.php';

	$start = microtime(true);
	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	//$tpl->parseString("{p: {em: and finally a colon {code: :dsaasd dsdaasd}}}");
	$tpl->parseURI('readme.dcy');

	foreach ($tpl->documentElement->childNodes as $node) {
		echo ($tpl->saveXML($node)), "\n";
	}

	echo '<pre style="white-space: pre-wrap;">';

	printf(
		"\nExecuted in %.6f seconds using %.2fMB of memory.</pre>",
		microtime(true) - $start,
		memory_get_peak_usage() / 1024 / 1024
	);