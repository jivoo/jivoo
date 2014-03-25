<?php
/**
 * Content format used by {@see TextEditor}. Will automatically convert links,
 * line breaks and paragraphs.
 * @package Jivoo\Editors
 */
class TextFormat implements IContentFormat {

  public function toHtml($text) {
    $html = preg_replace('/((\r\n|\n\r|\n|\r) *){2}/i', "</p><p>", $text);
    $html = preg_replace('/(\r\n|\n\r|\n|\r)/i', "<br />\n", $html);
    /** @todo Improve URL-detection */
    $html = preg_replace('/(https?:\/\/([^\n\r"< \Z()]+))/i',
      '<a href="\\1">\\2</a>', $html);
    if ($html == '') {
      return $html;
    }
    return '<p>' . $html . '</p>';
  }

  public function fromHtml($html) {
    $text = str_replace("\n", '', $html);
    $text = preg_replace('/^<p>(.*)<\/p>$/is', "\\1", $text);
    $text = preg_replace('/<\/p> *<p>/i', "\n\n", $text);
    $text = preg_replace('/<br *\/?>/i', "\n", $text);
    $text = preg_replace(
      '/<a href="(https?:\/\/.+?)">(.*?)<\/a>([\n\r "()]|\Z)/i',
      '\\1\\3', $text);
    return $text;
  }
}
