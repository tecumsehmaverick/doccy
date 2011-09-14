<?php

	require_once 'libs/doccy.php';

	$tpl = new Doccy\Template();
	$tpl->formatOutput = true;
	$tpl->openURI('test.txt');

	echo '<pre>';

	foreach ($tpl->documentElement->childNodes as $node) {
		echo htmlentities($tpl->saveXML($node)), "\n";
	}

?>