<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Helpers;

use Jivoo\Core\Utilities;

/**
 * HTML helper. Adds some useful methods when working with HTML views.
 */
class HtmlHelper extends Helper {
  /**
   * @var string Class to put on current links
   */
  private $classIfCurrent = 'current';

  /**
   * Get end tag for a begin tag
   * @param string $tag Begin tag, e.g. '<ul>'
   * @return string End tag, e.g. '</ul>'
   */
  public function getEndTag($tag) {
    if (!isset($this->endTags[$tag])) {
      $matches = array();
      preg_match('/<\s*([a-zA-Z0-9]+)/', $tag, $matches);
      $this->endTags[$tag] = '</' . $matches[1] . '>';
    }
    return $this->endTags[$tag];
  }

  /**
   * Convert an array of attributes into valid HTML.
   * @param string[] $attributes Attributes, see {@see Html::readAttributes}.
   * @return string Attributes
   */
  public function addAttributes($attributes) {
    $output = '';
    $attributes = Html::readAttributes($attributes);
    foreach ($attributes as $name => $value) {
      if (is_string($value) or $value === true) {
        $output .= ' ' . $name;
        if ($value !== true)
          $output .= '="' . h($value) . '"';
      }
    }
    return $output;
  }
  
  /**
   * Create an HTML tag.
   * @param string $tag Tag.
   * @param string[] $attributes Attributes, see {@see Html::readAttributes}.
   * @return Html Html node.
   */
  public function create($tag, $attributes = array()) {
    $html = new Html($tag);
    $html->attr($attributes);
    return $html;
  }

  /**
   * Insert an image.
   * @param $file Path to file (can be an asset or an absolute path).
   * @param string[] $attributes Attributes, see {@see Html::readAttributes}.
   * @return string HTML image.
   */
  public function img($file, $attributes = array()) {
    $img = $this->create('img');
    $img->attr('alt', $file);
    $img->attr($attributes);
    if (!Utilities::isAbsolutePath($file))
      $file = $this->view->file($file);
    $img->attr('src', $file);
    return $img->toString();
  }

  /**
   * Create a link
   * @param string $label Label for link
   * @param array|ILinkable|string|null $route Route for link, default is
   *        frontpage, see {@see \Jivoo\Routing\Routing}.
   * @param string[] $attributes Attributes, see {@see Html::readAttributes}.
   * @return string HTML link.
   */
  public function link($label, $route = null, $attributes = array()) {
    try {
      $url = $this->m->Routing->getLink($route);
      $a = $this->create('a', $attributes);
      if ($url != '')
        $a->attr('href', $url);
      if ($this->m->Routing->isCurrent($route))
        $a->addClass('current');
      $a->html($label);
      return $a->toString();
    }
    catch (InvalidRouteException $e) {
      Logger::logException($e);
      return '<a href="#invalid-route" class="invalid">' . $label . '</a>';
    }
  }

  /**
   * Clean a URL
   * @param string $url URL
   * @return string URL
   */
  public function cleanUrl($url) {
    if (preg_match('/^https?:\/\//i', $url) == 0) {
      $url = '';
    }
    return h($url);
  }

}

/**
 * An HTML element used by the {@see HtmlHelper}.
 */
class Html implements \ArrayAccess {
  /**
   * HTML5 tags that should not be closed.
   *
   * Source: http://xahlee.info/js/html5_non-closing_tag.html
   * @var array Associative array of lowercase tag-names and true-values.
   */
  private static $selfClosingTags = array('area' => true, 'base' => true,
    'br' => true, 'col' => true, 'command' => true, 'embed' => true,
    'hr' => true, 'img' => true, 'input' => true, 'keygen' => true,
    'link' => true, 'meta' => true, 'param' => true, 'source' => true,
    'track' => true, 'wbr' => true
  );

  /**
   * @var string HTML tag.
   */
  public $tag;
  
  /**
   * @var string[]
   */
  private $attributes = array();
  
  /**
   * @var string[]
   */
  private $properties = array();

  /**
   * @var string[]
   */
  private $classes = array();
  
  /**
   * @var string
   */
  private $content = '';
  
  /**
   * Construct HTML element.
   * @param string $tag HTML tag.
   */
  public function __construct($tag) {
    $this->tag = $tag;
  }
  
  /**
   * Get or set one or more attributes. Call without parameters to get all
   * attributes.
   * @param string|string[] $attribute An attribute, an associative array of
   * attributes to be read by {@see readAttributes}, or an attribute string
   * to be read by {@see readAttributeString}.
   * @param string|bool $value Value for attribute (if $attribute is a single
   * attribute name). Set to false to remove attribute.
   * @return string[]|string|null When called without parameters all attributes
   * are returned. When called with a single string parameter (without spaces
   * or "="-characters) the value of that attribute is returned (or null if
   * attribute is not set).
   */
  public function attr($attribute = null, $value = null) {
    if (!isset($attribute)) {
      $attributes = $this->attributes;
      if (count($this->classes) > 0)
        $attributes['class'] = implode(' ', $this->classes);
      return $attributes;
    }
    if (is_array($attribute)) {
      $this->attributes = array_merge($this->attributes, self::readAttributes($attribute));
    }
    else if (!isset($value)) {
      if (strpos($attribute, '=') !== false or strpos($attribute, ' ') !== false)
        $this->attributes = array_merge($this->attributes, self::readAttributeString($attribute));
      else if ($attribute == 'class')
        return implode(' ', $this->classes);
      else if (array_key_exists($attribute, $this->attributes))
        return $this->attributes[$attribute];
      else
        return null;
    }
    else if ($value === false){
      unset($this->attributes[$attribute]);
    }
    else {
      $this->attributes[$attribute] = $value;
    }
    if (isset($this->attributes['class'])) {
      $classes = explode(' ', $this->attributes['class']);
      foreach ($classes as $class) {
        $class = trim($class);
        if ($class != '')
          $this->addClass($class);
      }
      unset($this->attributes['class']);
    }
  }
  
  /**
   * Get or set data attributes.
   * @param string $key Data attribute.
   * @param string|null $value Value. Leave empty to get or set to false to
   * unset.
   * @return string Property value when called with one parameter.
   */
  public function data($key, $value = null) {
    return $this->attr('data-' . $key, $value);
  }
  
  /**
   * Get or set HTML content.
   * @param string $html HTML content.
   * @return string HTML content when called without parameters.
   */
  public function html($html = null) {
    if (isset($html))
      $this->content = $html;
    else
      return $this->content;
  }
  
  /**
   * Append HTML content.
   * @param string $html HTML content.
   */
  public function append($html) {
    $this->content .= $html;
  }
  
  /**
   * Prepend HTML content.
   * @param string $html HTML content.
   */
  public function prepend($html) {
    $this->content = $html . $this->content;
  }
  
  /**
   * Whether element has non-HTML property. If the property name exists as
   * an attribute the value is moved to the property and the attribute is
   * automatically removed.
   * @param string $property Property name.
   * @return bool True if property exists.
   */
  public function hasProp($property) {
    if (isset($this->attributes[$property])) {
      if (!isset($this->properties[$property]))
        $this->properties[$property] = $this->attributes[$property];
      unset($this->attributes[$property]); 
      return true;
    }
    return isset($this->properties[$property]);
  }
  
  /**
   * Get or set value of a non-HTML property. If the property name exists as
   * an attribute the attribute is automatically removed.
   * @param string $property Property name.
   * @param string|null $value Property value. Leave empty to get or set to
   * false to unset.
   * @return 
   */
  public function prop($property, $value = null) {
    if (isset($this->attributes[$property])) {
      if (!isset($this->properties[$property]))
        $this->properties[$property] = $this->attributes[$property];
      unset($this->attributes[$property]); 
    }
    if (isset($value)) {
      if ($value === false)
        unset($this->properties[$property]);
      else
        $this->properties[$property] = $value;
    }
    else if (isset($this->properties[$property])) {
      return $this->properties[$property];
    }
    else {
      return null;
    }
  }
  
  /**
   * Whether element has class.
   * @param string $class Class.
   * @return bool True if element has class.
   */
  public function hasClass($class) {
    return isset($this->classes[$class]);
  }
  
  /**
   * Add a class.
   * @param string $class Class name.
   */
  public function addClass($class) {
    $this->classes[$class] = $class;
  }
  
  /**
   * Remvoe a class.
   * @param string $class Class name.
   */
  public function removeClass($class) {
    unset($this->classes[$class]);
  }
  
  /**
   * Toggle a class.
   * @param string $class Class name.
   */
  public function toggleClass($class) {
    if (isset($this->classes[$class]))
      unset($this->classes[$class]);
    else
      $this->classes[$class] = $class;
  }
  
  /**
   * Convert to string.
   * @return string HTML string.
   */
  public function toString() {
    $output = '<' . $this->tag;
    foreach ($this->attr() as $name => $value) {
      if (is_string($value) or $value === true) {
        $output .= ' ' . $name;
        if ($value !== true)
          $output .= '="' . h($value) . '"';
      }
    }
    if ($this->content == '' and isset(self::$selfClosingTags[$this->tag]))
      return $output . ' />';
    $output .= '>';
    $output .= $this->content;
    $output .= '</' . $this->tag . '>';
    return $output;
  }

  /**
   * Convert to string.
   * @return string HTML string.
   */
  public function __toString() {
    return $this->toString();
  }
  
  /**
   * Get value of an attribute or property.
   * @param string $attribute Attribute or property.
   * @return string|null Value or null.
   */
  public function offsetGet($attribute) {
    if (isset($this->properties[$attribute]))
      return $this->properties[$attribute];
    return $this->attr($attribute);
  }

  /**
   * Set an attribute or property (if it exists).
   * @param string $attribute Attribute or property.
   * @param string|true $value Value.
   */
  public function offsetSet($attribute, $value) {
    if (isset($this->properties[$attribute]))
      $this->prop($attribute, $value);
    else
      $this->attr($attribute, $value);
  }

  /**
   * Remove an attribute or property.
   * @param string $attribute Attribute or property.
   */
  public function offsetUnset($attribute) {
    $this->prop($attribute, false);
    $this->attr($attribute, false);
  }

  /**
   * Whether an attribute or property exists.
   * @param string $attribute Attribute or property.
   * @return bool True if attrubute exists.
   */
  public function offsetExists($attribute) {
    if (isset($this->properties[$attribute]))
      return true;
    return array_key_exists($attribute, $this->attributes);
  }
  
  /**
   * Read an associative array of HTML attributes and convert all elements
   * without string keys using {@see readAttributeString}.
   * 
   * E.g. <code>array('class' => 'test', 'href="#"')</code> is converted to
   * <code>array('class' => 'test', 'href' => '#')</code>
   * @param string[] $attributes Attribute array.
   * @return string[] Output attribute array.
   */
  public static function readAttributes($attributes) {
    if (is_string($attributes))
      return self::readAttributeString($attributes);
    $result = array();
    if (isset($attributes['data'])) {
      foreach ($attributes['data'] as $key => $value)
        $result['data-' . $key] = $value;
      unset($attributes['data']);
    }
    foreach ($attributes as $key => $value) {
      if (is_int($key))
        $result = array_merge($result, self::readAttributeString($value));
      else
        $result[$key] = $value;
    }
    return $result;
  }
  
  /**
   * Convert an attribute string such as 'href="#" class="active" disabled' to an
   * associative array. Boolean true is used for attributes without values.
   * 
   * Lexical grammar for attribute string:
   * <code>
   * attrs    ::= ws {attr ws}
   * attr     ::= key [ws "=" ws value]
   * key      ::= keyc {keyc}
   * value    ::= valc {valc}
   *            | "\"" {strc} "\""
   * valc     ::= keyc | "0" | ... | "9"
   * keyc     ::= "_" | "-" | ":" | "a" | ... | "z" | "A" | ... | "Z"
   * strc     ::= any - ("\"" | "\\")
   *            | "\\" any
   * ws       ::= " " | "\t"
   * </code>
   * where <code>any</code> is any unicode character.
   * @param string $string Attribute string.
   * @return string[] Attribute array.
   */
  public static function readAttributeString($string) {
    preg_match_all('/([a-z_:-]+)\s*=\s*(?:([a-z0-9_:-]+)|"((?:[^"\\\\]|\\\\.)*)")|([a-z_-]+)/i', $string, $tokens, PREG_SET_ORDER);
    $attributes = array();
    foreach ($tokens as $token) {
      if (isset($token[4]) and $token[4] != '') {
        $attributes[$token[4]] = true;
      }
      else {
        $attr = $token[1];
        if ($token[2] != '')
          $attributes[$attr] = $token[2];
        else
          $attributes[$attr] = stripslashes($token[3]);
      }
    }
    return $attributes;
  }
}