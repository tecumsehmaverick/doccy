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

	function prettyPrint(\Doccy\Template $document, Options $options) {
		$xpath = new \DOMXPath($document);

		foreach ($xpath->query('//*') as $node) {
			if ($node->isPrettyPrintable() === false) continue;

			// Find all text nodes:
			foreach ($xpath->query('text()', $node) as $text) {
				var_dump($text->nodeValue);
			}
		}
	}

	/**
	 * Apply common tweaks to text to make the document appear "prettier".
	 *
	 * @param DOMDocument $document
	 */
	function prettifyTextNodes(\DOMDocument $document, Options $options) {
		$xpath = new \DOMXPath($document);
		$nodes = array();
		$results = $xpath->query('
			//address | //caption
			| //td | //th
			| //h1 | //h2 | //h3 | //h4 | //h5 | //h6
			| //li | //dt | //dd
			| //p
		');

		// Find nodes that may contain prettyable bits:
		foreach ($results as $node) {
			array_unshift($nodes, $node);
		}

		// Loop through the nodes, now in reverse order:
		foreach ($nodes as $node) {
			$search = $replace = array();
			$content = '';

			// Find content:
			while ($node->hasChildNodes()) {
				$content .= $document->saveXML($node->firstChild);
				$node->removeChild($node->firstChild);
			}

			// Make quotation marks pretty:
			if ($options->pretty_quotation_marks) {
				$search = array_merge(
					$search,
					array(
						'/(\w)\'(\w)|(\s)\'(\d+\w?)\b(?!\')/',	// apostrophe's
						'/(\S)\'(?=\s|[[:punct:]]|<|$)/',		// single closing
						'/\'/',									// single opening
						'/(\S)\"(?=\s|[[:punct:]]|<|$)/',		// double closing
						'/"/',									// double opening
					)
				);
				$replace = array_merge(
					$replace,
					array(
						'\1&#8217;\2',							// apostrophe's
						'\1&#8217;',							// single closing
						'&#8216;',								// single opening
						'\1&#8221;',							// double closing
						'&#8220;',								// double opening
					)
				);
			}

			// Make sentences pretty:
			if ($options->double_sentence_spacing) {
				$search = array_merge(
					$search,
					array(
						'/([!?.])(?:[ ])/',
					)
				);
				$replace = array_merge(
					$replace,
					array(
						'\1&#160; ',
					)
				);
			}

			// Make acronyms pretty:
			if ($options->convert_textual_elements) {
				$search = array_merge(
					$search,
					array(
						'/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/',
					)
				);
				$replace = array_merge(
					$replace,
					array(
						'<acronym title="\2">\1</acronym>',
					)
				);
			}

			// Make ellipses pretty:
			if ($options->pretty_ellipses) {
				$search = array_merge(
					$search,
					array(
						'/\.{3}/',
					)
				);
				$replace = array_merge(
					$replace,
					array(
						'\1&#8230;',
					)
				);
			}

			// Make hyphens pretty:
			if ($options->pretty_hyphens) {
				$search = array_merge(
					$search,
					array(
						'/--/',			// em dash
						'/-/',			// en dash
					)
				);
				$replace = array_merge(
					$replace,
					array(
						'&#8212;',		// em dash
						'&#8211;',		// en dash
					)
				);
			}

			if (!empty($search)) {
				$lines = preg_split("/(<.*>)/U", $content, -1, PREG_SPLIT_DELIM_CAPTURE);
				$content = ''; $apply = true;

				foreach ($lines as $line) {
					// Skip over code samples:
					if (preg_match('/^<(pre|code)/i', $line)) {
						$apply = false;
					}

					else if (preg_match('/$<\/(pre|code)>/i', $line)) {
						$apply = true;
					}

					if ($apply && !preg_match("/<.*>/", $line)) {
						$line = preg_replace($search, $replace, $line);
					}

					$content .= $line;
				}
			}

			// Prevent widows:
			if ($options->prevent_widowed_words) {
				$content = preg_replace(
					'/((^|\s)\S{0,20})\s(\S{0,20})$/',
					'\1&#160;\3', $content
				);
			}

			// Wrap dashes:
			if ($options->convert_textual_elements) {
				$content = str_replace(
					array(
						'&#8212;',
						'&#8211;'
					),
					array(
						'<span class="dash em">&#8212;</span>',
						'<span class="dash en">&#8211;</span>'
					),
					$content
				);
			}

			// Wrap ampersands:
			if ($options->convert_textual_elements) {
				$content = preg_replace(
					'/&#38;|&amp;/i',
					'<span class="ampersand">&#38;</span>', $content
				);
			}

		    // Wrap quotation marks:
			if ($options->convert_textual_elements) {
				$content = str_replace(
					array(
				    	'&#8216;',
				    	'&#8217;',
				    	'&#8220;',
				    	'&#8221;',
				    	'&#171;',
				    	'&#187;'
					),
					array(
				    	'<span class="quote left single">&#8216;</span>',
				    	'<span class="quote right single">&#8217;</span>',
				    	'<span class="quote left double">&#8220;</span>',
				    	'<span class="quote right double">&#8221;</span>',
				    	'<span class="quote left angle">&#8221;</span>',
				    	'<span class="quote right angle">&#8221;</span>'
					),
					$content
				);
			}

			// Wrap ellipsis:
			if ($options->convert_textual_elements) {
				$content = str_replace(
					'&#8230;', '<span class="ellipsis">&#8230;</span>', $content
				);
			}

			// Replace content:
			$fragment = $document->createDocumentFragment();
			$fragment->appendXML($content);
			$node->appendChild($fragment);
		}
	}