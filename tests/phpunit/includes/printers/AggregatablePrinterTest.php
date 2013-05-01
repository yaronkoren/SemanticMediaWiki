<?php

namespace SMW\Test;

use SMWDataItem;
use SMWDINumber;

use ReflectionClass;

/**
 * Tests for the SMW\AggregatablePrinter class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup QueryPrinter
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMW\AggregatablePrinter class
 *
 * @ingroup QueryPrinter
 */
class AggregatablePrinterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\AggregatablePrinter';
	}

	/**
	 * @test AggregatablePrinter::getResultText
	 *
	 * @since 1.9
	 */
	public function testGetResultTextErrorMessage() {
		$expectedMessage = wfMessage( 'smw-qp-aggregatable-empty-data' )->inContentLanguage()->text();
		$queryString = '[[Lula query]]';

		// Build SMWQuery object
		$qp = new \SMWQueryParser( $GLOBALS['smwgQFeatures'] );
		$qp->setDefaultNamespaces( $GLOBALS['smwgQDefaultNamespaces'] );
		$desc = $qp->getQueryDescription( $queryString );
		$query = new \SMWQuery( $desc );
		$query->setQueryString( $queryString );

		// Get SMWQueryResult object
		$queryResult = smwfGetStore()->getQueryResult( $query );

		// Access abstract class
		$abstractInstance = $this->getMockForAbstractClass( $this->getClass(), array( 'table' ) );

		// Make protected method accessible
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'getResultText' );
		$method->setAccessible( true );

		// Invoke the instance
		$result = $method->invokeArgs( $abstractInstance, array( $queryResult, SMW_OUTPUT_HTML ) );

		$this->assertEquals( '', $result );

		foreach( $queryResult->getErrors() as $error ) {
			$this->assertEquals( $expectedMessage, $error );
		}
	}

	/**
	 * @test AggregatablePrinter::addNumbersForDataItem
	 *
	 * @since 1.9
	 */
	public function testAddNumbersForDataItem() {
		$values = array();
		$expected = array();
		$keys = array( 'test', 'foo', 'bar' );

		// Access abstract class
		$abstractInstance = $this->getMockForAbstractClass( $this->getClass(), array( 'table' ) );

		// Make protected method accessible
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'addNumbersForDataItem' );
		$method->setAccessible( true );

		for ( $i = 1; $i <= 10; $i++ ) {

			// Select random array key
			$name = $keys[rand(0, 2)];

			// Get a random number
			$random = rand( 10, 500 );

			// Set expected result and create dataItem
			$expected[$name] = isset( $expected[$name] ) ? $expected[$name] + $random : $random;
			$dataItem = new SMWDINumber( $random );

			$this->assertEquals( $random, $dataItem->getNumber() );
			$this->assertEquals( SMWDataItem::TYPE_NUMBER, $dataItem->getDIType() );

			// Invoke the instance
			$result = $method->invokeArgs( $abstractInstance, array( $dataItem, &$values, $name ) );

			$this->assertInternalType( 'integer', $values[$name] );
			$this->assertEquals( $expected[$name], $values[$name] );
		}
	}
}
