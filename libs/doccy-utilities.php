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
