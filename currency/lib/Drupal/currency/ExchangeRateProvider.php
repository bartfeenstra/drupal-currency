<?php

/**
 * @file
 * Contains \Drupal\currency\ExchangeRateProvider.
 */

namespace Drupal\currency;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides currency exchange rates through plugins.
 */
class ExchangeRateProvider implements ExchangeRateProviderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The currency exchanger plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $currencyExchangeRateProviderManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $currency_exchange_rate_provider_manager
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   */
  function __construct(PluginManagerInterface $currency_exchange_rate_provider_manager, ConfigFactory $config_factory) {
    $this->currencyExchangeRateProviderManager = $currency_exchange_rate_provider_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Loads the plugin configuration.
   *
   * @return array
   *   Keys are currency_exchanger plugin names. Values are booleans that
   *   describe whether the plugins are enabled. Items are ordered by weight.
   */
  public function loadConfiguration() {
    $definitions = $this->currencyExchangeRateProviderManager->getDefinitions();
    $configuration = $this->configFactory->get('currency.exchange_rate_provider')->get('plugins') + array_fill_keys(array_keys($definitions), TRUE);

    return $configuration;
  }

  /**
   * Saves the configuration.
   *
   * @param array $configuration
   *   Keys are currency_exchanger plugin names. Values are booleans that
   *   describe whether the plugins are enabled. Items are ordered by weight.
   *
   * @return NULL
   */
  public function saveConfiguration(array $configuration) {
    $config = $this->configFactory->get('currency.exchange_rate_provider');
    $config->set('plugins', $configuration);
    $config->save();
  }

  /**
   * Returns enabled currency exchanger plugins, sorted by weight.
   *
   * @return \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderInterface[]
   */
  protected function getPlugins() {
    $names = array_keys(array_filter($this->loadConfiguration()));
    $plugins = array();
    foreach ($names as $name) {
      $plugins[$name] = $this->currencyExchangeRateProviderManager->createInstance($name, array());
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function load($currency_code_from, $currency_code_to) {
    if ($currency_code_from == $currency_code_to) {
      return 1;
    }
    foreach ($this->getPlugins() as $exchanger) {
      if ($rate = $exchanger->load($currency_code_from, $currency_code_to)) {
        return $rate;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $currency_codes) {
    $rates = array();

    foreach ($currency_codes as $currency_code_from => $currency_codes_to) {
      // Include all requested rates as unavailable from the start, so they are
      // included in the return value, even if they cannot be loaded later on.
      $rates[$currency_code_from] = array_fill_keys($currency_codes_to, NULL);

      // Set rates for identical source and destination currencies.
      foreach ($currency_codes_to as $index => $currency_code_to) {
        if ($currency_code_from == $currency_code_to) {
          $rates[$currency_code_from][$currency_code_to] = 1;
          // Prevent the rate from being loaded by any plugins.
          unset($currency_codes[$currency_code_from][$index]);
        }
      }
    }

    foreach ($this->getPlugins() as $exchanger) {
      foreach ($exchanger->loadMultiple($currency_codes) as $currency_code_from => $currency_codes_to) {
        foreach ($currency_codes_to as $currency_code_to => $rate) {
          if (!is_null($rate)) {
            $rates[$currency_code_from][$currency_code_to] = $rate;
            // Prevent the rate from being loaded again by other plugins.
            $index = array_search($currency_code_to, $currency_codes[$currency_code_from]);
            unset($currency_codes[$currency_code_from][$index]);
          }
        }
      }
    }

    return $rates;
  }
}