<?php

	/**
	 * @package doccy
	 */

	namespace Doccy\Parser;

	/**
	 * Main Doccy parser loop, finds the start and end of elements.
	 *
	 * @param Doccy\Utilities\Data	$data
	 * @param DOMElement			$parent
	 */
	function main(\Doccy\Utilities\Data $data, \DOMElement $parent) {
		$skip_next_close = 0;

		while ($data) {
			$token = $data->findToken('%[{}]|[^{}]+%');

			// Token position located:
			if ($token instanceof \Doccy\Utilities\Token) {
				list($before, $after) = $data->splitAt($token);

				// Open:
				if ($token->value == '{') {
					$result = openTag($after, $parent);

					// Not a valid open tag:
					if ($result === false) {
						$node = $parent->ownerDocument->createTextNode($token->value);
						$parent->appendChild($node);

						$data = $after;
						$skip_next_close++;
					}

					else {
						list($after, $element) = $result;

						$data = $after;
						$parent = $element;
					}

					continue;
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

					$data = $after;
					continue;
				}

				// Text:
				else {
					list($before, $after) = $data->splitAt($token);

					$node = $parent->ownerDocument->createTextNode($token->value);
					$parent->appendChild($node);

					$data = $after;
					continue;
				}
			}

			break;
		}
	}

	/**
	 * Parse the start of a Doccy tag, including attributes and element ID.
	 *
	 * @param Doccy\Utilities\Data	$data
	 * @param DOMElement			$parent
	 */
	function openTag(\Doccy\Utilities\Data $data, \DOMElement $parent) {
		$attributes = array();
		$name = $attribute = null;
		$ended = false;

		while ($data) {
			$token = $data->findToken('%^[a-z][a-z0-9]*|(^|\s+)[@#.][a-z]+|:\s*|[^:@]+%');

			// Ends here:
			if ($token->value->test('%^:\s*%')) {
				list($before, $after) = $data->splitAt($token);

				$data = $after;
				$ended = true;
				break;
			}

			// Attribute:
			else if ($token->value->test('%^\s*@%')) {
				list($before, $after) = $data->splitAt($token);

				$attribute = trim($token->value, '@ ');
				$attributes[$attribute] = null;
				$data = $after;
				continue;
			}

			// Class attribute:
			else if ($token->value->test('%^\s*[.]%')) {
				list($before, $after) = $data->splitAt($token);

				$value = trim($token->value, '. ');

				$attributes['class'] = (
					isset($attributes['class'])
						? $attributes['class'] . ' ' . $value
						: $value
				);

				$data = $after;
				continue;
			}

			// ID attribute:
			else if ($token->value->test('%^\s*#%')) {
				list($before, $after) = $data->splitAt($token);

				$value = trim($token->value, '# ');
				$attributes['id'] = $value;
				$data = $after;
				continue;
			}

			// Attribute value:
			else if (!is_null($attribute)) {
				list($before, $after) = $data->splitAt($token);

				$attributes[$attribute] .= trim($token->value);
				$attribute = null;
				$data = $after;
				continue;
			}

			// Element name:
			else if ($token->value->test('%^[a-z][a-z0-9]*$%')) {
				list($before, $after) = $data->splitAt($token);

				$name = (string)$token->value;
				$data = $after;
				continue;
			}

			break;
		}

		if (is_null($name) || $ended === false) {
			return false;
		}

		else {
			$element = $parent->ownerDocument->createElement($name);
			$parent->appendChild($element);

			foreach ($attributes as $name => $value) {
				$element->setAttribute($name, $value);
			}

			return array(
				$data,
				$element
			);
		}
	}

?>