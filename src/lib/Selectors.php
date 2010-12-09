<?php
/**
 * CSS Compressor [VERSION]
 * [DATE]
 * Corey Hart @ http://www.codenothing.com
 */ 

Class CSSCompression_Selectors
{
	/**
	 * Selector patterns
	 *
	 * @class Control: Compression Controller
	 * @param (string) token: Copy of the injection token
	 * @param (array) options: Reference to options
	 * @param (regex) rmark: Stop points during selector parsing
	 * @param (regex) ridclassend: End of a id/class string
	 * @param (regex) rquote: Checks for the next quote character
	 * @param (regex) rcomma: looks for an unescaped comma character
	 * @param (regex) rid: looks for an unescaped hash character
	 * @param (array) pseudos: Contains pattterns and replacments to space out pseudo selectors
	 */
	private $Control;
	private $token = '';
	private $options = array();
	private $rmark = "/(?<!\\\)(#|\.|=)/";
	private $ridclassend = "/(?<!\\\)[:#>~\[\+\*\. ]/";
	private $rquote = "/(?<!\\\)(\"|')?\]/";
	private $rcomma = "/(?<!\\\),/";
	private $rid = "/(?<!\\\)#/";
	private $pseudos = array(
		'patterns' => array(
			"/\:first-(letter|line)[,]/i",
			"/  /",
			"/:first-(letter|line)$/i",
		),
		'replacements' => array(
			":first-$1 ,",
			" ",
			":first-$1 ",
		)
	);

	/**
	 * Stash a reference to the controller on each instantiation
	 *
	 * @param (class) control: CSSCompression Controller
	 */
	public function __construct( CSSCompression_Control $control ) {
		$this->Control = $control;
		$this->token = $control->token;
		$this->options = &$control->Option->options;
	}

	/**
	 * Selector specific optimizations
	 *
	 * @param (array) selectors: Array of selectors
	 */
	public function selectors( &$selectors = array() ) {
		foreach ( $selectors as &$selector ) {
			// Auto ignore sections
			if ( strpos( $selector, $this->token ) === 0 ) {
				continue;
			}

			// Smart casing and token injection
			$selector = $this->parse( $selector );

			// Remove everything before final id in a selector
			if ( $this->options['strict-id'] ) {
				$selector = $this->strictid( $selector );
			}

			// Get rid of possible repeated selectors
			$selector = $this->repeats( $selector );

			// Add space after pseudo selectors (so ie6 doesn't complain)
			if ( $this->options['pseudo-space'] ) {
				$selector = $this->pseudoSpace( $selector );
			}
		}

		return $selectors;
	}

	/**
	 * Converts selectors like BODY => body, DIV => div
	 * and injects tokens wrappers for attribute values
	 *
	 * @param (string) selector: CSS Selector
	 */ 
	private function parse( $selector ) {
		$clean = '';
		$substr = '';
		$pos = 0;

		while ( preg_match( $this->rmark, $selector, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$substr = substr( $selector, $pos, $match[ 0 ][ 1 ] + 1 - $pos );
			$clean .= $this->options['lowercase-selectors'] ? strtolower( $substr ) : $substr;
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 1 ][ 0 ] );

			if ( $match[ 1 ][ 0 ] == '#' || $match[ 1 ][ 0 ] == '.' ) {
				if ( preg_match( $this->ridclassend, $selector, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
					$clean .= substr( $selector, $pos, $m[ 0 ][ 1 ] - $pos );
					$pos = $m[ 0 ][ 1 ];
				}
				else {
					$clean .= substr( $selector, $pos );
					$pos = strlen( $selector );
					break;
				}
			}
			else if ( preg_match( $this->rquote, $selector, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
				if ( $selector[ $pos ] == "\"" || $selector[ $pos ] == "'" ) {
					$pos++;
				}
				$clean .= $this->token . substr( $selector, $pos, $m[ 0 ][ 1 ] - $pos ) . $this->token . ']';
				$pos = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );
			}
			else {
				$clean .= substr( $selector, $pos );
				$pos = strlen( $selector );
				break;
			}
		}

		return $clean . ( $this->options['lowercase-selectors'] ? strtolower( substr( $selector, $pos ) ) : substr( $selector, $pos ) );
	}

	/**
	 * Promotes nested id's to the front of the selector
	 *
	 * @param (string) selector: CSS Selector
	 */
	private function strictid( $selector ) {
		$parts = preg_split( $this->rcomma, $selector );
		foreach ( $parts as &$s ) {
			if ( preg_match( $this->rid, $s ) ) {
				$p = preg_split( $this->rid, $s );
				$s = '#' . array_pop( $p );
			}
		}

		return implode( ',', $parts );
	}

	/**
	 * Removes repeated selectors that have been comma separated
	 *
	 * @param (string) selector: CSS Selector
	 */
	private function repeats( $selector ) {
		$parts = preg_split( $this->rcomma, $selector );
		$parts = array_flip( $parts );
		$parts = array_flip( $parts );
		return implode( ',', $parts );
	}

	/**
	 * Adds space after pseudo selector for ie6 like a:first-child{ => a:first-child {
	 *
	 * @param (string) selector: CSS Selector
	 */ 
	private function pseudoSpace( $selector ) {
		return preg_replace( $this->pseudos['patterns'], $this->pseudos['replacements'], $selector );
	}

	/**
	 * Access to private methods for testing
	 *
	 * @param (string) method: Method to be called
	 * @param (array) args: Array of paramters to be passed in
	 */
	public function access( $method, $args ) {
		if ( method_exists( $this, $method ) ) {
			if ( $method == 'selectors' ) {
				return $this->selectors( $args[ 0 ] );
			}
			else {
				return call_user_func_array( array( $this, $method ), $args );
			}
		}
		else {
			throw new CSSCompression_Exception( "Unknown method in Color Class - " . $method );
		}
	}
};

?>
