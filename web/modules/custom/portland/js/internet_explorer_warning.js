/**
 * @file
 * Internet Explorer warning block behaviors.
 */

(function($, Drupal) {
  /**
   * Filters the block list by a text input search string.
   *
   * The text input will have the selector `input.block-filter-text`.
   *
   * The target element to do searching in will be in the selector
   * `input.block-filter-text[data-element]`
   *
   * The text source where the text should be found will have the selector
   * `.block-filter-text-source`
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.internetExplorerWarning = {
    attach() {
      const $block = $('.js-internet-explorer-warning').once('js-internet-explorer-warning-done');

      if ($block) {
        var ua = window.navigator.userAgent;

        // Detect if IE <= 11, add message if detected
        if (ua.indexOf('Trident/') > 0 || ua.indexOf('MSIE ') > 0) {
          $block.css('display', 'flex')
        }
      }
    }
  };

})(jQuery, Drupal, Drupal.debounce);
