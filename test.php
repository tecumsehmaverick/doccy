<?php

	require_once 'libs/doccy.php';

	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	$tpl->openURI('readme.dy');

	echo '<pre style="white-space: pre-wrap;">';

	foreach ($tpl->documentElement->childNodes as $node) {
		echo htmlentities($tpl->saveXML($node)), "\n";
	}

?>