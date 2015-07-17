<?php

/**
 * @file
 * Contains \Drupal\Tests\currency\Unit\Element\CurrencyAmountUnitTest.
 */

namespace Drupal\Tests\currency\Unit\Element;

use Drupal\Core\Form\FormState;
use Drupal\currency\Element\CurrencyAmount;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\currency\Element\CurrencyAmount
 *
 * @group Currency
 */
class CurrencyAmountUnitTest extends UnitTestCase {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currencyStorage;

  /**
   * The element under test.
   *
   * @var \Drupal\currency\Element\CurrencyAmount
   */
  protected $element;

  /**
   * The input parser.
   *
   * @var \Drupal\currency\InputInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $input;

  /**
   * The form helper.
   *
   * @var \Drupal\currency\FormHelperInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formHelper;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->currencyStorage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');

    $this->formHelper = $this->getMock('\Drupal\currency\FormHelperInterface');

    $this->input = $this->getMock('\Drupal\currency\InputInterface');

    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];

    $this->element = new CurrencyAmount($configuration, $plugin_id, $plugin_definition, $this->stringTranslation, $this->currencyStorage, $this->input, $this->formHelper);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('currency')
      ->willReturn($this->currencyStorage);

    $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $map = array(
      array(
        'currency.form_helper',
        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
        $this->formHelper
      ),
      array(
        'currency.input',
        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
        $this->input
      ),
      array(
        'entity.manager',
        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
        $entity_manager
      ),
      array(
        'string_translation',
        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
        $this->stringTranslation
      ),
    );
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map));

    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();

    $form = CurrencyAmount::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf('\Drupal\currency\Element\CurrencyAmount', $form);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $info = $this->element->getInfo();
    $this->assertInternalType('array', $info);
    foreach ($info['#element_validate'] as $callback) {
      $this->assertTrue(is_callable($callback));
    }
    foreach ($info['#process'] as $callback) {
      $this->assertTrue(is_callable($callback));
    }
  }

  /**
   * @covers ::process
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider providerTestProcessWithInvalidMinimumAmount
   */
  public function testProcessWithInvalidMinimumAmount($amount) {
    $element = [
      '#minimum_amount' => $amount,
      '#maximum_amount' => FALSE,
      '#limit_currency_codes' => [],
    ];

    $form_state = new FormState();
    $form = [];

    $this->element->process($element, $form_state, $form);
  }

  /**
   * Provides data to self::testProcessWithInvalidMinimumAmount().
   */
  public function providerTestProcessWithInvalidMinimumAmount() {
    return [
      [TRUE],
      [NULL],
      [$this->randomMachineName()],
      [new \stdClass()],
    ];
  }

  /**
   * @covers ::process
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider providerTestProcessWithInvalidMaximumAmount
   */
  public function testProcessWithInvalidMaximumAmount($amount) {
    $element = [
      '#minimum_amount' => FALSE,
      '#maximum_amount' => $amount,
      '#limit_currency_codes' => [],
    ];

    $form_state = new FormState();
    $form = [];

    $this->element->process($element, $form_state, $form);
  }

  /**
   * Provides data to self::testProcessWithInvalidMaximumAmount().
   */
  public function providerTestProcessWithInvalidMaximumAmount() {
    return [
      [TRUE],
      [NULL],
      [$this->randomMachineName()],
      [new \stdClass()],
    ];
  }

  /**
   * @covers ::process
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider providerTestProcessWithInvalidLimitedCurrencyCodes
   */
  public function testProcessWithInvalidLimitedCurrencyCodes($limit_currency_codes, array $default_value) {
    $element = [
      '#default_value' => $default_value,
      '#minimum_amount' => FALSE,
      '#maximum_amount' => FALSE,
      '#limit_currency_codes' => $limit_currency_codes,
    ];

    $form_state = new FormState();
    $form = [];

    $this->element->process($element, $form_state, $form);
  }

  /**
   * Provides data to self::testProcessWithInvalidLimitedCurrencyCodes().
   */
  public function providerTestProcessWithInvalidLimitedCurrencyCodes() {
    $currency_code = $this->randomMachineName();
    $default_value = [
      'currency_code' => $currency_code,
    ];

    return [
      [TRUE, []],
      [FALSE, []],
      [NULL, []],
      [$this->randomMachineName(), []],
      [new \stdClass(), []],
      [
        array($this->randomMachineName(), $this->randomMachineName()),
        $default_value
      ],
    ];
  }

  /**
   * @covers ::process
   *
   * @depends      testGetInfo
   *
   * @dataProvider providerTestProcess
   */
  public function testProcess($default_currency_loadable) {
    $currency_code_a = $this->randomMachineName();
    $currency_code_b = $this->randomMachineName();
    $currency_code_c = $this->randomMachineName();

    $currency = $this->getMock('\Drupal\currency\Entity\CurrencyInterface');

    $currency_options = [
      $currency_code_a => $this->randomMachineName(),
      $currency_code_b => $this->randomMachineName(),
      $currency_code_c => $this->randomMachineName(),
    ];

    $this->formHelper->expects($this->atLeastOnce())
      ->method('getCurrencyOptions')
      ->willReturn($currency_options);

    $map = [
      [$currency_code_b, $default_currency_loadable ? $currency : NULL],
      ['XXX', $default_currency_loadable ? NULL : $currency],
    ];
    $this->currencyStorage->expects($this->atLeastOnce())
      ->method('load')
      ->willReturnMap($map);

    $limit_currency_codes = [$currency_code_a, $currency_code_b];

    $element = [
        '#default_value' => [
          'amount' => mt_rand(),
          'currency_code' => $currency_code_b,
        ],
        '#required' => TRUE,
        '#limit_currency_codes' => $limit_currency_codes,
      ] + $this->element->getInfo();

    $form_state = new FormState();
    $form = [];

    $element = $this->element->process($element, $form_state, $form);
    $this->assertEmpty(array_diff($limit_currency_codes, array_keys($element['currency_code']['#options'])));
    $this->assertEmpty(array_diff(array_keys($element['currency_code']['#options']), $limit_currency_codes));
  }

  /**
   * Provides data to self::testProcess().
   */
  public function providerTestProcess() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

}
