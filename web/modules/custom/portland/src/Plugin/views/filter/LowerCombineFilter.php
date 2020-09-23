<?php

namespace Drupal\portland\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Combine;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("combine_lower")
 */
class LowerCombineFilter extends Combine {

  public function query() {
    $this->view->_build('field');
    $fields = [];
    // Only add the fields if they have a proper field and table alias.
    foreach ($this->options['fields'] as $id) {
      // Overridden fields can lead to fields missing from a display that are
      // still set in the non-overridden combined filter.
      if (!isset($this->view->field[$id])) {
        // If fields are no longer available that are needed to filter by, make
        // sure no results are shown to prevent displaying more then intended.
        $this->view->build_info['fail'] = TRUE;
        continue;
      }
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias exists.
      $field->ensureMyTable();
      if (!empty($field->field_alias) && !empty($field->field_alias)) {
        $fields[] = "$field->tableAlias.$field->realField";
      }
    }
    if ($fields) {
      foreach ($fields as $key => &$field) {
        $field = 'LOWER(' . $field . ')';
      }
      // We do not use the CONCAT_WS operator when there is only a single field.
      // Using the CONCAT_WS operator with a single field is not a problem for
      // the by core supported databases. Only the MS SQL Server requires the
      // CONCAT_WS operator to be used with at least three arguments.
      if (count($fields) == 1) {
        $expression = reset($fields);
      }
      else {
        // Multiple fields are separated by 3 spaces so that so that search
        // strings that contain spaces are still only matched to single field
        // values and not to multi-field values that exist only because we do
        // the concatenation/LIKE trick.
        $expression = implode(", ' ', ", $fields);
        $expression = "CONCAT_WS(' ', $expression)";
      }
      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($expression);
      }
    }
  }

  /**
   * By default things like opEqual uses add_where, that doesn't support
   * complex expressions, so override opEqual (and all operators below).
   */
  public function opEqual($expression) {
    $placeholder = $this->placeholder();
    $operator = $this->operator();
    $this->query->addWhereExpression($this->options['group'], "$expression $operator $placeholder", [$placeholder => strtolower($this->value)]);
  }

  protected function opContains($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", [$placeholder => '%' . strtolower($this->connection->escapeLike($this->value)) . '%']);
  }

  /**
   * Filters by one or more words.
   *
   * By default opContainsWord uses add_where, that doesn't support complex
   * expressions.
   *
   * @param string $expression
   */
  protected function opContainsWord($expression) {
    $placeholder = $this->placeholder();

    // Don't filter on empty strings.
    if (empty($this->value)) {
      return;
    }

    // Match all words separated by spaces or sentences encapsulated by double
    // quotes.
    preg_match_all(static::WORDS_PATTERN, ' ' . $this->value, $matches, PREG_SET_ORDER);

    // Switch between the 'word' and 'allwords' operator.
    $type = $this->operator == 'word' ? 'OR' : 'AND';
    $group = $this->query->setWhereGroup($type);
    $operator = $this->connection->mapConditionOperator('LIKE');
    $operator = isset($operator['operator']) ? $operator['operator'] : 'LIKE';

    foreach ($matches as $match_key => $match) {
      $temp_placeholder = $placeholder . '_' . $match_key;
      // Clean up the user input and remove the sentence delimiters.
      $word = trim($match[2], ',?!();:-"');
      $this->query->addWhereExpression($group, "$expression $operator $temp_placeholder", [$temp_placeholder => '%' . strtolower($this->connection->escapeLike($word)) . '%']);
    }
  }

  protected function opStartsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", [$placeholder => strtolower($this->connection->escapeLike($this->value)) . '%']);
  }

  protected function opNotStartsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", [$placeholder => strtolower($this->connection->escapeLike($this->value)) . '%']);
  }

  protected function opEndsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", [$placeholder => '%' . strtolower($this->connection->escapeLike($this->value))]);
  }

  protected function opNotEndsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", [$placeholder => '%' . strtolower($this->connection->escapeLike($this->value))]);
  }

  protected function opNotLike($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", [$placeholder => '%' . strtolower($this->connection->escapeLike($this->value)) . '%']);
  }

  protected function opRegex($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression REGEXP $placeholder", [$placeholder => strtolower($this->value)]);
  }

  protected function opEmpty($expression) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhereExpression($this->options['group'], "$expression $operator");
  }

}
