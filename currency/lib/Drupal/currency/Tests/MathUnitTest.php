<?php

/**
 * @file Contians \Drupal\currency\Tests\MathUnitTest.
 */

namespace Drupal\currency\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\currency\Math.
 */
class MathUnitTest extends UnitTestCase {

  /**
   * The math service under test.
   *
   * @var \Drupal\currency\Math|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $math;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\currency\Math unit test',
      'group' => 'Currency',
    );
  }

  /**
   * {@inheritdoc
   */
  public function setUp() {
    $this->math = $this->getMockBuilder('\Drupal\currency\Math')
      ->setMethods(array('isExtensionLoaded'))
      ->getMock();
  }

  /**
   * Tests add().
   */
  public function testAddNative() {
    $this->prepareNative();

    $this->assertSame(3.7539016, $this->math->add(1.2005729, 2.5533287));
    $this->assertSame(3.7539016, $this->math->add('1.2005729', '2.5533287'));
  }

  /**
   * Tests add().
   */
  public function testAddBCMath() {
    $this->prepareBCMath();

    $this->assertSame('3.753901600', $this->math->add(1.2005729, 2.5533287));
    $this->assertSame('3.753901600', $this->math->add('1.2005729', '2.5533287'));
  }

  /**
   * Tests subtract().
   */
  public function testSubtractNative() {
    $this->prepareNative();

    $this->assertSame(-1.3527558, $this->math->subtract(1.2005729, 2.5533287));
    $this->assertSame(-1.3527558, $this->math->subtract('1.2005729', '2.5533287'));
  }

  /**
   * Tests subtract().
   */
  public function testSubtractBCMath() {
    $this->prepareBCMath();

    $this->assertSame('-1.352755800', $this->math->subtract(1.2005729, 2.5533287));
    $this->assertSame('-1.352755800', $this->math->subtract('1.2005729', '2.5533287'));
  }

  /**
   * Tests multiply().
   */
  public function testMultiplyNative() {
    $this->prepareNative();

    $this->assertSame(3.06545724201223, $this->math->multiply(1.2005729, 2.5533287));
    $this->assertSame(3.06545724201223, $this->math->multiply('1.2005729', '2.5533287'));
  }

  /**
   * Tests multiply().
   */
  public function testMultiplyBCMath() {
    $this->prepareBCMath();

    $this->assertSame('3.065457242', $this->math->multiply(1.2005729, 2.5533287));
    $this->assertSame('3.065457242', $this->math->multiply('1.2005729', '2.5533287'));
  }

  /**
   * Tests divide().
   */
  public function testDivideNative() {
    $this->prepareNative();

    $this->assertSame(1.2005729, $this->math->divide(3.06545724201223, 2.5533287));
    $this->assertSame(1.2005729, $this->math->divide('3.06545724201223', '2.5533287'));
  }

  /**
   * Tests divide().
   */
  public function testDivideBCMath() {
    $this->prepareBCMath();

    $this->assertSame('3.000000000', $this->math->divide(6, 2));
    $this->assertSame('1.200572900', $this->math->divide('3.06545724201223', '2.5533287'));
  }

  /**
   * Tests round().
   */
  public function testRoundNative() {
    $this->prepareNative();

    // Test a value that must be rounded down.
    $this->assertSame(2.5525, $this->math->round(2.5533287, 0.0025));
    $this->assertSame(2.5525, $this->math->round('2.5533287', '0.0025'));
    // Test a value that must be rounded up.
    $this->assertSame(100.77, $this->math->round(100.7654, 0.01));
    $this->assertSame(100.77, $this->math->round('100.7654', '0.01'));
  }

  /**
   * Tests round().
   */
  public function testRoundBCMath() {
    $this->prepareBCMath();

    // Test a value that must be rounded down.
    $this->assertSame('2.5525', $this->math->round(2.5533287, 0.0025));
    $this->assertSame('2.5525', $this->math->round('2.5533287', '0.0025'));
    // Test a value that must be rounded up.
    $this->assertSame('100.77', $this->math->round(100.7654, 0.01));
    $this->assertSame('100.77', $this->math->round('100.7654', '0.01'));
  }

  /**
   * Prepares a test method for testing native math support.
   */
  protected function prepareNative() {
    $this->math->expects($this->any())
      ->method('isExtensionLoaded')
      ->will($this->returnValue(FALSE));
  }

  /**
   * Prepares a test method for testing BCMath support.
   */
  protected function prepareBCMath() {
    $this->math->expects($this->any())
      ->method('isExtensionLoaded')
      ->with('bcmath')
      ->will($this->returnValue(TRUE));
  }
}
