<?php

namespace WMDE\VueJsTemplating\FilterExpressionParsing;

use RuntimeException;
use WMDE\VueJsTemplating\JsParsing\FilterApplication;
use WMDE\VueJsTemplating\JsParsing\JsExpressionParser;
use WMDE\VueJsTemplating\JsParsing\ParsedExpression;

class ParseResult {

	/**
	 * @var string[]
	 */
	private $expressions;

	/**
	 * @var FilterCall[]
	 */
	private $filterCalls = [];
    /**
     * @var null
     */
    private $environment;
    /**
     * @var null
     */
    private $context;

    /**
	 * @param string[] $expressions
	 * @param FilterCall[] $filterCalls
	 */
	public function __construct( array $expressions, array $filterCalls, $environment = null, $context = null ) {
		$this->expressions = $expressions;
		$this->filterCalls = $filterCalls;
        $this->environment = $environment;
        $this->context = $context;
    }

	/**
	 * @return string[]
	 */
	public function expressions() {
		return $this->expressions;
	}

	/**
	 * @return FilterCall[]
	 */
	public function filterCalls() {
		return $this->filterCalls;
	}

	/**
	 * @param JsExpressionParser $expressionParser
	 * @param callable[] $filters Indexed by name
	 *
	 * @return ParsedExpression
	 */
	public function toExpression( JsExpressionParser $expressionParser, array $filters ) {
		if ( count( $this->filterCalls ) === 0 ) {
			return $expressionParser->parse( $this->expressions[0] );
		}

		$nextFilterArguments = $this->parseExpressions( $expressionParser, $this->expressions );

		$result = null;
		foreach ( $this->filterCalls as $filterCall ) {
			if ( !array_key_exists( $filterCall->filterName(), $filters ) ) {
				throw new RuntimeException( "Filter '{$filterCall->filterName()}' is undefined" );
			}
			$filter = $filters[$filterCall->filterName()];

			if ($filter instanceof EnvironmentAware) {
			    $filter->setEnvironment($this->environment);
            }

            if ($filter instanceof ContextAware) {
                $filter->setContext($this->context);
            }

			$filerArguments = array_merge(
				$nextFilterArguments,
				$this->parseExpressions( $expressionParser, $filterCall->arguments() )
			);

			$result = new FilterApplication( $filter, $filerArguments );
			$nextFilterArguments = [ $result ];
		}

		return $result;
	}

	/**
	 * @param string[] $expressions
	 */
	private function parseExpressions( JsExpressionParser $expressionParser, array $expressions ) {
		return array_map(
			function ( $exp ) use ( $expressionParser ) {
				return $expressionParser->parse( $exp );
			},
			$expressions
		);
	}

}
