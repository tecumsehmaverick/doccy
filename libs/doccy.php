<?php

	/**
	 * Doccy is a simple brace based text formatter.
	 *
	 * @package doccy
	 */

	namespace Doccy;

	require_once 'doccy-parser.php';
	require_once 'doccy-utilities.php';

	class Template extends \DOMDocument {
		/**
		 * Open a URI and parse it into the template.
		 *
		 * @param string $uri
		 */
		public function parseURI($uri) {
			if (file_exists($uri) === false) {
				throw new Exception(sprintf(
					"File '%s' does not exist.", $uri
				));
			}

			$this->parseString(file_get_contents($uri));
		}

		/**
		 * Parse a string into the template.
		 *
		 * @param string $input
		 */
		public function parseString($input) {
			$data = new \Doccy\Utilities\Data(input);
			$this->appendChild(
				$this->createElement('data')
			);

			Parser\main($data, $this);
			Utilities\wrapFloatingText($this);
		}

		/**
		 * Convert the XML document into a string.
		 */
		public function __toString() {
			$output = null;

			foreach ($tpl->documentElement->childNodes as $node) {
				$output .= ($tpl->saveXML($node)) . "\n";
			}

			return $output;
		}
	}

?>