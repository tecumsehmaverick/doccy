<?php

	/**
	 * @package doccy
	 */

	namespace Doccy\Utilities;

	/**
	 * Represents a value discovered in data, including the position and length.
	 */
	class Token {
		public $length;
		public $position;
		public $value;

		/**
		 * Create a new Token object.
		 *
		 * @param Data		$value
		 * @param integer	$position
		 */
		public function __construct(Data $value, $position) {
			$this->length = strlen($value);
			$this->position = $position;
			$this->value = $value;
		}
	}

	/**
	 * Represents a chunk of a Doccy template.
	 */
	class Data {
		/**
		 * The template data.
		 */
		protected $data;

		/**
		 * Create a new Data object.
		 *
		 * @param string $data
		 */
		public function __construct($data) {
			$this->data = $data;
		}

		/**
		 * Return the data as a string.
		 */
		public function __toString() {
			return (string)$this->data;
		}

		/**
		 * Search the data using a regular expression, returning the first found
		 * match as a Token object.
		 *
		 * @param string $expression
		 * @return Token on success null on failure
		 */
		public function findToken($expression) {
			preg_match($expression, $this->data, $match, PREG_OFFSET_CAPTURE);

			if (!isset($match[0][0]) || !isset($match[0][1])) return null;

			return new Token(new Data($match[0][0]), $match[0][1]);
		}

		/**
		 * Split the data using a Token, returning two new Data objects, one before
		 * and the other after the Token.
		 *
		 * @param Token $token
		 * @return array
		 */
		public function splitAt(Token $token) {
			return array(
				new Data(
					substr($this->data, 0, $token->position)
				),
				new Data(
					substr($this->data, $token->position + $token->length)
				)
			);
		}

		/**
		 * Test the data with a regular expression.
		 *
		 * @return boolean
		 */
		public function test($expression) {
			return (boolean)preg_match($expression, $this->data);
		}
	}

	/**
	 * Wrap floating pieces of text in paragraph elements.
	 *
	 * @param DOMDocument $document
	 */
	function wrapFloatingText(\DOMDocument $document) {
		$xpath = new \DOMXPath($document);
		$nodes = array(); $breaks = array(
			'section', 'article', 'aside', 'header', 'footer', 'nav',
			'dialog', 'figure', 'address', 'p', 'hr', 'pre',
			'blockquote', 'ol', 'ul', 'li', 'dl', 'dt', 'dd', 'img',
			'iframe', 'embed', 'object', 'param', 'video', 'audio',
			'source', 'canvas', 'map', 'area', 'table', 'caption',
			'colgroup', 'col', 'tbody', 'thead', 'tfoot', 'tr', 'td',
			'th', 'form', 'fieldset', 'label', 'input', 'button',
			'select', 'datalist', 'optgroup', 'option', 'textarea',
			'keygen', 'output', 'details', 'datagrid', 'command',
			'bb', 'menu', 'legend', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
		);

		// Find nodes that may contain paragraphs:
		foreach ($xpath->query('
			//data
			| //blockquote
			| //div
			| //header
			| //footer
			| //aside
			| //article
			| //section
		') as $node) {
			array_unshift($nodes, $node);
		}

		// Loop through the nodes, now in reverse order:
		foreach ($nodes as $node) {
			$default = array(
				'type'	=> 'inline',
				'value'	=> ''
			);
			$groups = array($default);
			$content = '';

			// Group text between paragraph breaks:
			foreach ($node->childNodes as $child) {
				if (in_array($child->nodeName, $breaks)) {
					array_push($groups,
						array(
							'type'	=> 'break',
							'value'	=> $document->saveXML($child)
						)
					);

					array_push($groups, $default);
				}

				else {
					$current = array_pop($groups);
					$current['value'] .= $document->saveXML($child);
					array_push($groups, $current);
				}
			}

			// Join together again:
			foreach ($groups as $current) {
				if ($current['type'] == 'break') {
					$content .= $current['value'];
				}

				else if (trim($current['value'])) {
					$value = preg_replace('/((\r\n|\n)\s*){2,}/', "</p><p>", trim($current['value']));
					$value = preg_replace('/[\r\n\t](?<=\S)/', '<br />', $value);
					$value = preg_replace('/\s{2,}/', ' ', $value);

					$content .= "<p>$value</p>";
				}
			}

			// Remove children:
			while ($node->hasChildNodes()) {
				$node->removeChild($node->firstChild);
			}

			// Replace content:
			if ($content) {
				try {
					$fragment = $document->createDocumentFragment();
					$fragment->appendXML($content);
					$node->appendChild($fragment);
				}

				catch (Exception $e) {
					// Ignore...
				}
			}
		}
	}