<?php

namespace SMW\Tests;

use SMW\TransientStatsdCollector;
use Onoi\BlobStore\BlobStore;
use Onoi\BlobStore\Container;

/**
 * @covers \SMW\TransientStatsdCollector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TransientStatsdCollectorTest extends \PHPUnit_Framework_TestCase {

	private $blobStore;
	private $container;

	protected function setUp() {

		$this->container = $this->getMockBuilder( Container::class )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $this->container ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TransientStatsdCollector::class,
			new TransientStatsdCollector( $this->blobStore, 42 )
		);
	}

	public function testSaveOnDestruct() {

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);
	}

	public function testIncr() {

		$this->container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$this->container->expects( $this->once() )
			->method( 'get' )
			->will( $this->returnValue( 10 ) );

		$this->container->expects( $this->once() )
			->method( 'set' )
			->with(
				$this->equalTo( 'Foo.bar' ),
				$this->equalTo( 11 ) );

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);

		$instance->incr( 'Foo.bar' );
	}

	public function testSet() {

		$this->container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$this->container->expects( $this->once() )
			->method( 'get' )
			->will( $this->returnValue( 10 ) );

		$this->container->expects( $this->once() )
			->method( 'set' )
			->with(
				$this->equalTo( 'Foo.bar' ),
				$this->equalTo( 10 ) );

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);
		$instance->set( 'Foo.bar', 10 );
	}

	public function testCalcMedian() {

		$this->container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$this->container->expects( $this->once() )
			->method( 'get' )
			->will( $this->returnValue( 10 ) );

		$this->container->expects( $this->once() )
			->method( 'set' )
			->with(
				$this->equalTo( 'Foo.bar' ),
				$this->equalTo( 7.5 ) );

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);

		$instance->calcMedian( 'Foo.bar', 5 );
	}

	public function testStats_Simple() {

		$this->container->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( array( 'Foo' => 1, 'Bar' => 1 ) ) );

		$expected = array(
			'Foo' => 1,
			'Bar' => 1
		);

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

	public function testStats_SimpleHierarchy() {

		$this->container->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( array( 'Foo.foobar' => 1, 'Bar' => 1 ) ) );

		$expected = array(
			'Foo' => array( 'foobar' => 1 ),
			'Bar' => 1
		);

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

	public function testStats_ExtendedHierarchy() {

		$this->container->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( array( 'Foo.foobar' => 5, 'Bar' => 1, 'Foo.foobar.baz' => 1 ) ) );

		$expected = array(
			'Foo' => array( 'foobar' => array( 5, 'baz' => 1 ) ),
			'Bar' => 1
		);

		$instance = new TransientStatsdCollector(
			$this->blobStore,
			42
		);

		$this->assertEquals(
			$expected,
			$instance->getStats()
		);
	}

}
