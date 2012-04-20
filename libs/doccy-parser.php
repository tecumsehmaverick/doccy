<?php

	/**
	 * @package doccy
	 */

	namespace Doccy\Parser;
	use Doccy\Element;
	use Doccy\Template;
	use StringParser\Data;
	use StringParser\Token;

	/**
	 * Main Doccy parser loop, finds the start and end of elements.
	 *
	 * @param Doccy\Utilities\Data $data
	 * @param DOMElement $parent
	 */
	function main(Data $data, Template $template) {
		$parent = $template->documentElement;
		$skip_next_close = 0;

		while ($data->valid()) {
			$token = $data->after('%(\\\[{}])|[{}]|[^{}\\\]+|\\\%');

			// Token position located:
			if (!$token instanceof Token) break;

			// Move data to the token:
			$data->move($token);

			// Open:
			if ($token->value == '{') {
				$element = openTag($data, $parent);

				// Not a valid open tag:
				if ($element === false) {
					$node = $parent->ownerDocument->createTextNode($token->value);
					$parent->appendChild($node);
					$data->move($token);
					$skip_next_close++;
				}

				else {
					$parent = $element;
				}
			}

			// Close token:
			else if ($token->value == '}') {
				if ($skip_next_close > 0) {
					$node = $parent->ownerDocument->createTextNode($token->value);
					$parent->appendChild($node);
					$skip_next_close--;
				}

				else if (isset($parent->parentNode)) {
					$parent = $parent->parentNode;
				}
			}

			// Escaped open/close token:
			else if ($token->value == '\\{' || $token->value == '\\}') {
				$node = $parent->ownerDocument->createTextNode(
					trim($token->value, '\\')
				);
				$parent->appendChild($node);
			}

			// Text:
			else {
				$node = $parent->ownerDocument->createTextNode($token->value);
				$parent->appendChild($node);
			}

			continue;
		}
	}

	/**
	 * Parse the start of a Doccy tag, including attributes and element ID.
	 *
	 * @param Doccy\Utilities\Data $data
	 * @param DOMElement $parent
	 */
	function openTag(Data $data, Element $parent) {
		$attributes = array();
		$name = $attribute = null;
		$ended = false;

		while ($data->valid()) {
			$token = $data->after('%^[a-z][a-z0-9]*|:\s+|(^|\s+)[@#.\%][a-z][a-z0-9\-]*|.+?%');

			// Token position located:
			if (!$token instanceof Token) break;

			// Move data to the token:
			$data->move($token);

			// Ends here:
			if ($token->test('%^:\s+%')) {
				$ended = true;
				break;
			}

			// Attribute:
			else if (
				($token->test('%^\s*@%') && is_null($attribute))
				|| $token->test('%^\s+@%')
			) {
				$attribute = trim($token->value, "@\r\n\t ");
				$attributes[$attribute] = null;
			}

			// Data attribute:
			else if (
				($token->test('%^\s*[\%]%') && is_null($attribute))
				|| $token->test('%^\s+[\%]%')
			) {
				$attribute = 'data-' . trim($token->value, "%\r\n\t ");
				$attributes[$attribute] = null;
			}

			// Class attribute:
			else if (
				($token->test('%^\s*[.]%') && is_null($attribute))
				|| $token->test('%^\s+[.]%')
			) {
				$value = trim($token->value, ".\r\n\t ");

				$attributes['class'] = (
					isset($attributes['class'])
						? $attributes['class'] . ' ' . $value
						: $value
				);

				$attribute = null;
			}

			// ID attribute:
			else if (
				($token->test('%^\s*#%') && is_null($attribute))
				|| $token->test('%^\s+#%')
			) {
				$value = trim($token->value, "#\r\n\t ");
				$attributes['id'] = $value;
				$attribute = null;
			}

			// Attribute value:
			else if (!is_null($attribute)) {
				// Trim any spaces off the start:
				if (strlen($attributes[$attribute]) == 0) {
					$value = ltrim($token->value);
				}

				else {
					$value = $token->value;
				}

				// Quoted value:
				if (substr($value, 0, 1) == '"' && substr($value, -1, 1) == '"') {
					$value = substr($value, 1, -1);
				}

				$attributes[$attribute] .= $value;
			}

			// Element name:
			else if ($token->test('%^[a-z][a-z0-9]*$%') && is_null($name)) {
				$name = $token->value;
			}

			// Break on on-whitespace:
			else if ($token->test('%^\s+$%') === false) {
				break;
			}

			continue;
		}

		// Not a valid element:
		if (is_null($name) || $ended === false) {
			return false;
		}

		// Add element and attributes:
		else {
			$element = $parent->ownerDocument->createElement($name);
			$parent->appendChild($element);

			foreach ($attributes as $name => $value) {
				$element->setAttribute($name, $value);
			}

			return $element;
		}
	}