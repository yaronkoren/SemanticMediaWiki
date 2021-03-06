<?php

namespace SMW\MediaWiki;

use Title;
use UnexpectedValueException;

/**
 * A convenience class to encapsulate MW related database interaction
 *
 * @note This is an internal class and should not be used outside of smw-core
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class MwTitleLookup {

	/** @var Database */
	protected $database = null;

	protected $namespace = null;

	/**
	 * @since 1.9.2
	 *
	 * @param Database $database
	 */
	public function __construct( Database $database ) {
		$this->database = $database;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $namespace
	 *
	 * @return TitleLookup
	 */
	public function byNamespace( $namespace ) {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return Title[]
	 * @throws UnexpectedValueException
	 */
	public function selectAll() {

		if ( $this->namespace === null ) {
			throw new UnexpectedValueException( 'Unrestricted selection without a namespace is not supported' );
		}

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$fields = array( 'cat_title' );
			$conditions = '';
			$options = array( 'USE INDEX' => 'cat_title' );
		} else {
			$tableName = 'page';
			$fields = array( 'page_namespace', 'page_title' );
			$conditions = array( 'page_namespace' => $this->namespace );
			$options = array( 'USE INDEX' => 'PRIMARY' );
		}

		$res = $this->database->select(
			$tableName,
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $startId
	 * @param int $endId
	 *
	 * @return Title[]
	 * @throws UnexpectedValueException
	 */
	public function selectByIdRange( $startId = 0, $endId = 0 ) {

		if ( $this->namespace === null ) {
			throw new UnexpectedValueException( 'Unrestricted selection without a namespace is not supported' );
		}

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$fields = array( 'cat_title', 'cat_id' );
			$conditions = array( "cat_id BETWEEN $startId AND $endId" );
			$options = array( 'ORDER BY' => 'cat_id ASC', 'USE INDEX' => 'cat_title' );
		} else {
			$tableName = 'page';
			$fields = array( 'page_namespace', 'page_title', 'page_id' );
			$conditions = array( "page_id BETWEEN $startId AND $endId" ) + array( 'page_namespace' => $this->namespace );
			$options = array( 'ORDER BY' => 'page_id ASC', 'USE INDEX' => 'PRIMARY' );
		}

		$res = $this->database->select(
			$tableName,
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function selectMaxId() {

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$var = 'MAX(cat_id)';
		} else {
			$tableName = 'page';
			$var = 'MAX(page_id)';
		}

		return (int)$this->database->selectField(
			$tableName,
			$var,
			false,
			__METHOD__
		);
	}

	protected function makeTitlesFromSelection( $res ) {

		$pages = array();

		if ( $res === false ) {
			return $pages;
		}

		foreach ( $res as $row ) {
			$pages[] = $this->newTitleFromRow( $row );
		}

		return $pages;
	}

	private function newTitleFromRow( $row ) {

		if ( $this->namespace === NS_CATEGORY ) {
			$ns = NS_CATEGORY;
			$title = $row->cat_title;
		} else {
			$ns =  $row->page_namespace;
			$title = $row->page_title;
		}

		return Title::makeTitle( $ns, $title );
	}

}
