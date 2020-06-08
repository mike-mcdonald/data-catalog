<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_token_info().
 *
 * @ingroup portland
 */
function portland_token_info()
{
  $info = [];

  $info['types']['server'] = [
    'name' => t('Server vars'),
    'description' => t('Tokens from _SERVER.'),
  ];

  $info['tokens']['server']['HTTP_REFERER'] = [
    'name' => t('HTTP_REFERER'),
    'description' => t("Referrer header value from _SERVER"),
  ];

  $info['tokens']['server']['HTTP_USER_AGENT'] = [
    'name' => t('HTTP_USER_AGENT'),
    'description' => t("User-agent header value from _SERVER"),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup portland
 */
function portland_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata)
{
  $replacements = [];
  $sanitize = !empty($options['sanitize']);

  // Text format tokens.
  if ($type == 'server') {
    foreach ($tokens as $name => $original) {
      $replacements[$original] = $_SERVER[$name];
      if ($sanitize) {
        $replacements[$original] = filter_xss($replacements[$original]);
      }
    }
  }

  return $replacements;
}
