<?php

	/**
	 * Doccy is a simple brace based text formatter.
	 *
	 * @package doccy
	 */

	namespace Doccy;

	require_once 'doccy-parser.php';
	require_once 'doccy-utilities.php';

	/**
	 * Take a Doccy template and make it programmable.
	 */
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

			$this->registerNodeClass('DOMElement', 'Doccy\\Element');
			$this->appendChild(
				$this->createElement('data')
			);

			if ($options === null) {
				$options = new Utilities\Options();
			}

			Parser\main($data, $this);
			Utilities\wrapFloatingText($this, $options);
			Utilities\prettyPrintText($this, $options);
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

	/**
	 * Represents an element in a Doccy template.
	 */
	class Element extends \DOMElement {
		/**
		 * Can this element be pretty printed? Preformatted HTML elements
		 * will return false.
		 *
		 * @return boolean
		 */
		public function isPrettyPrintable() {
			$parent = $this;

			// This element is not pretty printable:
			switch (strtolower($this->nodeName)) {
				case 'code':
				case 'samp':
				case 'kbd':
				case 'var':
				case 'pre':
					return false;
			}

			// One of it's parents is not pretty printable:
			while ($parent = $parent->parentNode) {
				if ($parent instanceof Template) continue;
				if ($parent->isPrettyPrintable()) continue;

				return false;
			}

			// Ok, go for broke.
			return true;
		}

		/**
		 * Is this element a "block" level element?
		 *
		 * @return boolean
		 */
		public function isBlockLevel() {
			switch (strtolower($this->nodeName)) {
				case 'a':
				case 'abbr':
				case 'acronym':
				case 'dfn':
				case 'em':
				case 'strong':
				case 'i':
				case 'b':
				case 'big':
				case 'small':
				case 'tt':
				case 'span':
				case 'cite':
				case 'del':
				case 'ins':
				case 'q':
				case 'sub':
				case 'sup':
				case 'th':
				case 'td':
				case 'dt':
				case 'dd':
				case 'li':
					return false;
			}

			return true;
		}
	}