<?php

	/**
	 * Reusable utility for parsing strings.
	 *
	 * @package StringParser
	 */

	namespace StringParser;

	/**
	 * Represents a chunk of source code.
	 */
	class Data {
		/**
		 * Maximum length that regular expressions can match against on data after token.
		 */
		const LIMIT_AFTER = 1024;

		/**
		 * Maximum length that regular expressions can match against on data before token.
		 */
		const LIMIT_BEFORE = 256;

		/**
		 * The template data.
		 */
		protected $data;

		/**
		 * The total length of data available.
		 */
		public $size;

		/**
		 * Data after the current token.
		 */
		public $after;

		/**
		 * Data before the current token.
		 */
		public $before;

		/**
		 * The position that the current token begins.
		 */
		public $begin;

		/**
		 * The position that the current token ends.
		 */
		public $end;

		/**
		 * Create a new StringParser.
		 *
		 * @param string $data
		 */
		public function __construct($data) {
			$this->data = $data;
			$this->size = strlen($data);
			$this->end = 0;
			$this->begin = 0;
		}

		/**
		 * Return the data as a string.
		 */
		public function __toString() {
			return (string)$this->data;
		}

		/**
		 * Perform a forwards search of the data and return a Token
		 * representing the expression matched, and its position.
		 *
		 * @param string	$expression	Return matches of regex
		 * @param boolean	$test		Perform simple test instead
		 * @param integer	$limit		Maximum number of characters
		 * @return Token on success null on failure
		 */
		public function after($expression = null, $test = false, $limit = self::LIMIT_AFTER) {
			if (isset($this->after) && $limit == self::LIMIT_AFTER) {
				$after = $this->after;
			}

			else {
				$after = substr($this->data, $this->end, $this->end + $limit);
				$this->after = $after;
			}

			if ($test === false) {
				if ($expression === null) {
					return new Token(substr($this->data, $this->end), $this->end);
				}

				preg_match($expression, $after, $match, PREG_OFFSET_CAPTURE);

				if (!isset($match[0][0]) || !isset($match[0][1])) return null;

				return new Token($match[0][0], $match[0][1] + $this->end);
			}

			else {
				if ($expression === null) {
					return $this->end < $this->size;
				}

				return (boolean)preg_match($expression, $after);
			}
		}

		/**
		 * Perform a backwards search of the data and return a Token
		 * representing the expression matched, and its position.
		 *
		 * @param string	$expression	Return matches of regex
		 * @param boolean	$test		Perform simple test instead
		 * @param integer	$limit		Maximum number of characters
		 * @return Token on success null on failure
		 */
		public function before($expression = null, $test = false, $limit = self::LIMIT_BEFORE) {
			if (isset($this->before) && $limit == self::LIMIT_BEFORE) {
				$before = $this->before;
			}

			else {
				$limit = max(0, $this->begin - $limit);
				$before = substr($this->data, $limit, $this->begin - $limit);
				$this->before = $before;
			}

			if ($test === false) {
				if ($expression === null) {
					return new Token($before, $this->begin);
				}

				preg_match($expression, $before, $match, PREG_OFFSET_CAPTURE);

				if (!isset($match[0][0]) || !isset($match[0][1])) return null;

				return new Token($match[0][0], $match[0][1] + $limit);
			}

			else {
				if ($expression === null) {
					return $this->begin > 0;
				}

				return (boolean)preg_match($expression, $before);
			}
		}

		/**
		 * Move the internal cursor to the position of a token.
		 *
		 * @param Token $token
		 * @return array
		 */
		public function move(Token $token) {
			$this->begin = $token->begin;
			$this->end = $token->end;

			unset($this->after, $this->before);
		}

		/**
		 * Is there still data to parse?
		 *
		 * @return boolean
		 */
		public function valid() {
			return $this->end < $this->size;
		}
	}

	/**
	 * Represents a value discovered in data, including the position and length.
	 */
	class Token {
		/**
		 * The position that the Token starts at.
		 */
		public $begin;

		/**
		 * The position that the Token ends at.
		 */
		public $end;

		/**
		 * The value between the start and end positions.
		 */
		public $value;

		/**
		 * Create a new Token object.
		 *
		 * @param string $value
		 * @param integer $begin
		 */
		public function __construct($value, $begin) {
			$this->begin = $begin;
			$this->end = $begin + strlen($value);
			$this->value = $value;
		}

		/**
		 * Return the data as a string.
		 */
		public function __toString() {
			return (string)$this->value;
		}

		/**
		 * Extract values from the token using a regular expression.
		 *
		 * @param string $expression
		 * @param integer $capture
		 * @return string
		 */
		public function extract($expression, $capture = 0) {
			$valid = (boolean)preg_match($expression, $this->value, $match);

			// Nothing to return:
			if ($valid === false || isset($match[$capture]) === false) {
				return null;
			}

			return $match[$capture];
		}

		/**
		 * Test the token value with a regular expression.
		 *
		 * @param string $expression
		 * @return boolean
		 */
		public function test($expression) {
			return (boolean)preg_match($expression, $this->value);
		}
	}