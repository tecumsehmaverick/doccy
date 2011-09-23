<?php

	/**
	 * @package doccy
	 */

	namespace Doccy\Utilities;

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

		/**
		 * Is there more data to parse?
		 *
		 * @return boolean
		 */
		public function valid() {
			return empty($this->data) === false;
		}
	}

	/**
	 * Standard options for customised formatting.
	 */
	class Options {
		/**
		 * Convert acronyms, ampersands, elipses and hyphens
		 * into stylable HTML.
		 */
		public $convert_textual_elements = false;

		/**
		 * Use clasic "Double spacing" between sentences instead
		 * of modern single "French spacing".
		 */
		public $double_sentence_spacing = false;

		/**
		 * Insert a non breaking space into the last sentence
		 * of a paragraph or heading.
		 */
		public $prevent_widowed_words = true;

		/**
		 * Convert three full stops (...) into ellipses.
		 */
		public $pretty_ellipses = true;

		/**
		 * Convert double hyphens (--) into mdashes.
		 */
		public $pretty_hyphens = true;

		/**
		 * Convert quotation marks to typographers quotation marks
		 */
		public $pretty_quotation_marks = true;
	}

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
		 * @param Data $value
		 * @param integer $position
		 */
		public function __construct(Data $value, $position) {
			$this->length = strlen($value);
			$this->position = $position;
			$this->value = $value;
		}
	}

	/**
	 * Wrap floating pieces of text in paragraph elements.
	 *
	 * @param DOMDocument $document
	 */
	function wrapFloatingText(\DOMDocument $document, Options $options) {
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

	/**
	 * Apply common tweaks to text to make the document appear "prettier".
	 *
	 * @param DOMDocument $document
	 */
	function prettyPrintText(\Doccy\Template $document, Options $options) {
		$xpath = new \DOMXPath($document);
		$search = $replace = array();

		// Make quotation marks pretty:
		if ($options->pretty_quotation_marks) {
			$search += array(
				100 => '/(\w)\'(\w)|(\s)\'(\d+\w?)\b(?!\')/',	// apostrophe's
				101 => '/(\S)\'(?=\s|[[:punct:]]|<|$)/',		// single closing
				102 => '/\'/',									// single opening
				103 => '/(\S)\"(?=\s|[[:punct:]]|<|$)/',		// double closing
				104 => '/"/'									// double opening
			);
			$replace += array(
				100 => '\1’\2',									// apostrophe's
				101 => '\1’'	,								// single closing
				102 => '‘',										// single opening
				103 => '\1”',									// double closing
				104 => '“'										// double opening
			);
		}

		// Make sentences pretty:
		if ($options->double_sentence_spacing) {
			$search += array(
				110 => '/([!?.])(?:[ ])/'
			);
			$replace += array(
				110 => '\1&#160; '
			);
		}

		// Make acronyms pretty:
		if ($options->convert_textual_elements) {
			$search += array(
				120 => '/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/'
			);
			$replace += array(
				120 => '<acronym title="\2">\1</acronym>'
			);
		}

		// Make ellipses pretty:
		if ($options->pretty_ellipses) {
			$search += array(
				130 => '/\.{3}/'
			);
			$replace += array(
				130 => '\1…'
			);
		}

		// Make hyphens pretty:
		if ($options->pretty_hyphens) {
			$search += array(
				140 => '/--/',	// em dash
				141 => '/-/'	// en dash
			);
			$replace += array(
				140 => '—',		// em dash
				141 => '–'		// en dash
			);
		}

		if ($options->convert_textual_elements) {
			// Make acronyms pretty:
			$search += array(
				150 => '/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/'
			);
			$replace += array(
				150 => '<acronym title="\2">\1</acronym>'
			);

			// Wrap dashes:
			$search += array(
				160 => '/—/',
				161 => '/–/'
			);
			$replace += array(
				160 => '<span class="dash em">\0</span>',
				161 => '<span class="dash en">\0</span>'
			);

			// Wrap ampersands:
			$search += array(
				170 => '/&#38;|&amp;/i'
			);
			$replace += array(
				170 => '<span class="ampersand">\0</span>',
			);

			// Wrap quotation marks:
			$search += array(
				180 => '/‘/',
				181 => '/’/',
				182 => '/“/',
				183 => '/”/',
				184 => '/«/',
				185 => '/»/'
			);
			$replace += array(
				180 => '<span class="quote left single">\0</span>',
				181 => '<span class="quote right single">\0</span>',
				182 => '<span class="quote left double">\0</span>',
				183 => '<span class="quote right double">\0</span>',
				184 => '<span class="quote left angle">\0</span>',
				185 => '<span class="quote right angle">\0</span>'
			);

			// Wrap ellipses:
			$search += array(
				190 => '/…/'
			);
			$replace += array(
				190 => '<span class="ellipsis">\0</span>',
			);
		}

		// Prettify text nodes:
		foreach ($xpath->query('//text()') as $text) {
			if ($text->parentNode->isPrettyPrintable() === false) continue;

			$value = $text->nodeValue;

			// Escape nasties:
			if ($options->convert_textual_elements) {
				$value = str_replace(
					array('&', '<', '>'),
					array('&amp;', '&lt;', '&gt;'),
					$value
				);
			}

			// Apply prettification rules:
			$value = preg_replace($search, $replace, $value);

			// Markup may have been added, replace with fragment:
			if ($options->convert_textual_elements) {
				$fragment = $document->createDocumentFragment();
				$fragment->appendXML($value);
				$text->parentNode->replaceChild($fragment, $node);
			}

			else {
				$text->nodeValue = $value;
			}
		}

		// Whitespace cleanup:
		foreach ($xpath->query('//*') as $node) {
			if ($node->isBlockLevel() === false) continue;
			if ($node->isPrettyPrintable() === false) continue;

			$widow_text = null;

			// Find the last text node that could
			// potentially be a widowed word:
			foreach ($node->childNodes as $child) {
				if ($child instanceof \Doccy\Element) {
					if (
						$options->prevent_widowed_words
						&& $child->isBlockLevel() === false
						&& $child->isPrettyPrintable()
					) {
						foreach ($xpath->query('.//text()', $child) as $text) {
							if (preg_match('/\s/', $text->nodeValue)) {
								$widow_text = $text;
							}
						}
					}
				}

				// Trim unwanted space off the ends:
				else if ($child instanceof \DOMText) {
					$child->nodeValue = preg_replace('/^\s+|\s+$/', ' ', $child->nodeValue);

					if (!trim($child->nodeValue)) {
						$child->nodeValue = '';
					}

					if (
						$options->prevent_widowed_words
						&& preg_match('/\s/', $child->nodeValue)
					) {
						$widow_text = $child;
					}
				}
			}

			// Insert a non-breaking space to prevent widowed words
			// at the end of sentences.
			if (
				$options->prevent_widowed_words
				&& $widow_text !== null
			) {
				$text = $widow_text;
				$value = $text->nodeValue;
				$regex = null;

				// Before a block element:
				if (
					$text->nextSibling instanceof \Doccy\Element
					&& $text->nextSibling->isBlockLevel()
				) {
					$regex = '/((^|\s)\S{0,20})\s(\S{1,20}\s?)$/';
				}

				// Before an inline element, or the last text node:
				else if (
					$text->nextSibling === null
					|| (
						$text->nextSibling instanceof \Doccy\Element
						&& strlen(trim($text->nextSibling->nodeValue)) < 16
					)
				) {
					$regex = '/((^|\s)\S{0,20})\s(\S{0,20})$/';
				}

				// Replace that basterd.
				if ($regex !== null) {
					$value = preg_replace(
						$regex,
						//utf8_encode("\\1\xa0\\3"),
						'\1★\3',
						$value
					);
				}

				$text->nodeValue = $value;
			}
		}
	}