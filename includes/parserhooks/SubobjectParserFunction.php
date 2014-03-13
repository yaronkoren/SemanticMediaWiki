<?php

namespace SMW;

use Parser;

/**
 * Provides the {{#subobject}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SubobjectParserFunction {

	/** @var ParserData */
	protected $parserData;

	/** @var Subobject */
	protected $subobject;

	/** @var MessageFormatter */
	protected $msgFormatter;

	/** @var boolean */
	protected $objectReference = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $msgFormatter
	 */
	public function __construct( ParserData $parserData, Subobject $subobject, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->subobject = $subobject;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * Enables/disables to create an object reference pointing to the original
	 * subject
	 *
	 * @since 1.9
	 *
	 * @param boolean $objectReference
	 *
	 * @return SubobjectParserFunction
	 */
	public function setObjectReference( $objectReference ) {
		$this->objectReference = $objectReference;
		return $this;
	}

	/**
	 * Generates an Id in accordance to the available settings
	 *
	 * @since 1.9
	 *
	 * @param ArrayFormatter $parameters
	 *
	 * @return string
	 */
	protected function getId( ArrayFormatter $parameters ) {

		$isAnonymous = in_array( $parameters->getFirst(), array( null, '' ,'-' ) );

		if ( $this->objectReference || $isAnonymous ) {
			$id = $this->subobject->generateId( new HashIdGenerator( $parameters->toArray(), '_' ) );
		} else {
			$id = $parameters->getFirst();
		}

		$this->objectReference = $this->objectReference && !$isAnonymous;

		return $id;
	}

	/**
	 * Add values to the subobject instance
	 *
	 * @since 1.9
	 *
	 * @param ArrayFormatter $parameters
	 */
	protected function addSubobjectValues( ArrayFormatter $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		// Initialize semantic container for a given identifier
		$this->subobject->setSemanticData( $this->getId( $parameters ) );

		foreach ( $this->transformParameters( $parameters ) as $property => $values ){
			foreach ( $values as $value ) {

				if ( $property === '@sortkey' ) {
					$property = '_SKEY';
				}

				$dataValue = DataValueFactory::getInstance()->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );
			}
		}
	}

	protected function transformParameters( ArrayFormatter $parameters ) {

		if ( $this->objectReference ) {
			$parameters->addParameter(
				$parameters->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		return $this->getSortByReference( $parameters->toArray() );
	}

	protected function getSortByReference( array $params ) {

		if ( isset( $params['@sortby'] ) ) {
			$property = array_pop( $params['@sortby'] );

			if ( isset( $params[ $property ] ) ) {
				$params['@sortkey'] = $params[ $property ];
			}

			unset( $params['@sortby'] );
		}

		return $params;
	}

	/**
	 * Parse parameters and return results to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @param ArrayFormatter $params
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		// Add values to the instantiated subobject
		$this->addSubobjectValues( $parameters );

		// Store subobject to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$this->subobject->getProperty(),
			$this->subobject->getContainer()
		);

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->msgFormatter->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

}
