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
		 */
		public function openURI($uri) {
			if (file_exists($uri) === false) {
				throw new Exception(sprintf(
					"File '%s' does not exist.", $uri
				));
			}

			$data = new \Doccy\Utilities\Data(file_get_contents($uri));
			$this->appendChild(
				$this->createElement('data')
			);

			Parser\main($data, $this);
			Utilities\wrapFloatingText($this);
		}
	}

?>