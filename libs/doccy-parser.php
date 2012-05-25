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
				$element = opening_tag($data, $parent);

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
	function opening_tag(Data $data, Element $parent) {
		$attributes = array();
		$name = $attribute = null;
		$ended = false;

		while ($data->valid()) {
			$token = $data->after('%^[a-z][a-z0-9]*|[\\\]?:\s+|(^|\s+)[@#.\%][a-z][a-z0-9\-]*|.+?%');

			// Token position located:
			if (!$token instanceof Token) break;

			// Move data to the token:
			$data->move($token);

			// Ends here:
			if (strpos($token->value, ':') === 0 && $token->test('%^:\s+%')) {
				$ended = true;
				break;
			}

			// Attribute:
			else if (is_attribute($token, $attribute, '@')) {
				$attribute = trim($token->value, "@\r\n\t ");
				$attributes[$attribute] = null;
			}

			// Data attribute:
			else if (is_attribute($token, $attribute, '[\%]')) {
				$attribute = 'data-' . trim($token->value, "%\r\n\t ");
				$attributes[$attribute] = null;
			}

			// Class attribute:
			else if (is_attribute($token, $attribute, '[.]')) {
				$value = trim($token->value, ".\r\n\t ");

				$attributes['class'] = (
					isset($attributes['class'])
						? $attributes['class'] . ' ' . $value
						: $value
				);

				$attribute = null;
			}

			// ID attribute:
			else if (is_attribute($token, $attribute, '#')) {
				$value = trim($token->value, "#\r\n\t ");
				$attributes['id'] = $value;
				$attribute = null;
			}

			// Quoted attribute value:
			else if ($attribute !== null && $token->value == '"') {
				$value = attribute_value($data);
				$attributes[$attribute] = $value;
				$attribute = null;
			}

			// Attribute value:
			else if ($attribute !== null) {
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
			else if ($name === null && $token->test('%^[a-z][a-z0-9]*$%')) {
				$name = $token->value;
			}

			// Break on on-whitespace:
			else if ($token->test('%^\s+$%') === false) {
				break;
			}

			continue;
		}

		// Not a valid element:
		if ($name === null || $ended === false) {
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

	/**
	 * Parse double quoted attribute values.
	 *
	 * @param Data $data
	 * @return string
	 */
	function attribute_value(Data $data) {
		$value = '';

		while ($data->valid()) {
			$token = $data->after('%[\\\][\\\"]|"|.[^\\\"]+%');

			// Token position located:
			if (!$token instanceof Token) break;

			// Move data to the token:
			$data->move($token);

			// String ends:
			if ($token->value == '"') {
				break;
			}

			else if ($token->value == '\\"') {
				$value .= '"';
			}

			else {
				$value .= $token->value;
			}
		}

		return $value;
	}

	/**
	 * Determine if an attribute is being set.
	 *
	 * The first time an attribute is set it should not start with a space, any
	 * following times it should.
	 *
	 * @param Token $token
	 * @param string|null $attribute
	 * @param string $expression
	 * @return boolean
	 */
	function is_attribute(Token $token, $attribute, $expression) {
		$without_space = '%^\s*' . $expression . '%';
		$with_space = '%^\s+' . $expression . '%';

		return (
			($attribute === null && $token->test($without_space))
			|| $token->test($with_space)
		);
	}