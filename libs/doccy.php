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
		public function parseURI($uri, Utilities\Options $options = null) {
			if (file_exists($uri) === false) {
				throw new Exception(sprintf(
					"File '%s' does not exist.", $uri
				));
			}

			$this->parseString(file_get_contents($uri), $options);
		}

		/**
		 * Parse a string into the template.
		 *
		 * @param string $input
		 */
		public function parseString($input, Utilities\Options $options = null) {
			$data = new \Doccy\Utilities\Data($input);
			$this->appendChild(
				$this->createElement('data')
			);

			if ($options === null) {
				$options = new Utilities\Options();
			}

			Parser\main($data, $this);
			Utilities\wrapFloatingText($this, $options);
			Utilities\prettifyTextNodes($this, $options);
		}

		/**
		 * Convert the XML document into a string.
		 */
		public function __toString() {
			$output = '';

			foreach ($this->documentElement->childNodes as $node) {
				$output .= ($this->saveXML($node)) . "\n";
			}

			return $output;
		}
	}

?>