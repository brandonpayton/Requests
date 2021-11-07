<?php

namespace WpOrg\Requests\Tests\Response;

use stdClass;
use WpOrg\Requests\Exception;
use WpOrg\Requests\Exception\InvalidArgument;
use WpOrg\Requests\Response\Headers;
use WpOrg\Requests\Tests\TestCase;

/**
 * @coversDefaultClass \WpOrg\Requests\Response\Headers
 */
final class HeadersTest extends TestCase {

	/**
	 * Test receiving an Exception when no key is provided when setting an entry.
	 *
	 * @covers ::offsetSet
	 *
	 * @return void
	 */
	public function testOffsetSetInvalidKey() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Object is a dictionary, not a list');

		$headers   = new Headers();
		$headers[] = 'text/plain';
	}

	/**
	 * Test array access for the object is supported and supported in a case-insensitive manner.
	 *
	 * @covers ::offsetSet
	 * @covers ::offsetGet
	 *
	 * @dataProvider dataCaseInsensitiveArrayAccess
	 *
	 * @param string $key Key to request.
	 *
	 * @return void
	 */
	public function testCaseInsensitiveArrayAccess($key) {
		$headers                 = new Headers();
		$headers['Content-Type'] = 'text/plain';

		$this->assertSame('text/plain', $headers[$key]);
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function dataCaseInsensitiveArrayAccess() {
		return array(
			'access using case as set' => array('Content-Type'),
			'access using lowercase'   => array('content-type'),
			'access using uppercase'   => array('CONTENT-TYPE'),
		);
	}

	/**
	 * Test that when multiple headers are set using the same key, requesting the key will return the
	 * combined values flattened into a single, comma-separated string.
	 *
	 * @covers ::offsetSet
	 * @covers ::offsetGet
	 * @covers ::flatten
	 *
	 * @return void
	 */
	public function testMultipleHeaders() {
		$headers           = new Headers();
		$headers['Accept'] = 'text/html;q=1.0';
		$headers['Accept'] = '*/*;q=0.1';

		$this->assertSame('text/html;q=1.0,*/*;q=0.1', $headers['Accept']);
	}

	/**
	 * Test that null is returned when a non-registered header is requested.
	 *
	 * @covers ::offsetGet
	 *
	 * @return void
	 */
	public function testOffsetGetReturnsNullForNonRegisteredHeader() {
		$headers                 = new Headers();
		$headers['Content-Type'] = 'text/plain';

		$this->assertNull($headers['not-content-type']);
	}

	/**
	 * Test retrieving all values for a given header (case-insensitively).
	 *
	 * @covers ::getValues
	 *
	 * @dataProvider dataGetValues
	 *
	 * @param string      $key      Key to request.
	 * @param string|null $expected Expected return value.
	 *
	 * @return void
	 */
	public function testGetValues($key, $expected) {
		$headers                   = new Headers();
		$headers['Content-Type']   = 'text/plain';
		$headers['Content-Length'] = 10;
		$headers['Accept']         = 'text/html;q=1.0';
		$headers['Accept']         = '*/*;q=0.1';

		$this->assertSame($expected, $headers->getValues($key));
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function dataGetValues() {
		return array(
			'using case as set, single entry header' => array(
				'key'      => 'Content-Type',
				'expected' => array(
					'text/plain',
				),
			),
			'using lowercase, single entry header' => array(
				'key'      => 'content-length',
				'expected' => array(
					10,
				),
			),
			'using uppercase, multiple entry header' => array(
				'key'      => 'ACCEPT',
				'expected' => array(
					'text/html;q=1.0',
					'*/*;q=0.1',
				),
			),
			'non-registered string key' => array(
				'key'      => 'my-custom-header',
				'expected' => null,
			),
			'non-registered integer key' => array(
				'key'      => 10,
				'expected' => null,
			),
		);
	}

	/**
	 * Tests receiving an exception when an invalid offset is passed to getValues().
	 *
	 * @covers ::getValues
	 *
	 * @dataProvider dataGetValuesInvalidOffset
	 *
	 * @param mixed $key Requested offset.
	 *
	 * @return void
	 */
	public function testGetValuesInvalidOffset($key) {
		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('Argument #1 ($offset) must be of type string|int');

		$headers = new Headers();
		$headers->getValues($key);
	}

	/**
	 * Data Provider.
	 *
	 * @return array
	 */
	public function dataGetValuesInvalidOffset() {
		return array(
			'null'          => array(null),
			'boolean false' => array(false),
		);
	}

	/**
	 * Test iterator access for the object is supported.
	 *
	 * Includes making sure that:
	 * - keys are handled case-insensitively.
	 * - multiple keys with the same name are flattened into one value.
	 *
	 * @covers ::getIterator
	 * @covers ::flatten
	 *
	 * @return void
	 */
	public function testIteration() {
		$headers                   = new Headers();
		$headers['Content-Type']   = 'text/plain';
		$headers['Content-Length'] = 10;
		$headers['Accept']         = 'text/html;q=1.0';
		$headers['Accept']         = '*/*;q=0.1';

		foreach ($headers as $name => $value) {
			switch (strtolower($name)) {
				case 'accept':
					$this->assertSame('text/html;q=1.0,*/*;q=0.1', $value, 'Accept header does not match');
					break;
				case 'content-type':
					$this->assertSame('text/plain', $value, 'Content-Type header does not match');
					break;
				case 'content-length':
					$this->assertSame('10', $value, 'Content-Length header does not match');
					break;
				default:
					throw new Exception('Invalid offset key: ' . $name);
			}
		}
	}

	/**
	 * Tests flattening of data.
	 *
	 * @covers ::flatten
	 *
	 * @dataProvider dataFlatten
	 *
	 * @param string|array $input    Value to flatten.
	 * @param string       $expected Expected output value.
	 *
	 * @return void
	 */
	public function testFlatten($input, $expected) {
		$headers = new Headers();
		$this->assertSame($expected, $headers->flatten($input));
	}

	/**
	 * Data Provider.
	 *
	 * @return array
	 */
	public function dataFlatten() {
		return array(
			'string'            => array('text', 'text'),
			'empty array'       => array(array(), ''),
			'array with values' => array(array('text', 10, 'more text'), 'text,10,more text'),
		);
	}

	/**
	 * Tests receiving an exception when an invalid value is passed to flatten().
	 *
	 * @covers ::flatten
	 *
	 * @dataProvider dataFlattenInvalidValue
	 *
	 * @param mixed $input Value to flatten.
	 *
	 * @return void
	 */
	public function testFlattenInvalidValue($input) {
		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('Argument #1 ($value) must be of type string|array');

		$headers = new Headers();
		$headers->flatten($input);
	}

	/**
	 * Data Provider.
	 *
	 * @return array
	 */
	public function dataFlattenInvalidValue() {
		return array(
			'null'          => array(null),
			'boolean false' => array(false),
			'plain object'  => array(new stdClass()),
		);
	}
}
