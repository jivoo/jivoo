<?php
// Module
// Name           : Theme
// Version        : 0.2.0
// Description    : The PeanutCMS theme system
// Author         : PeanutCMS
// Dependencies   : errors configuration templates

/*
 * Class for loading and manipulating the theme
 *
 * @package PeanutCMS
 */

/**
 * Theme class
 */
class Theme extends ModuleBase {

  /**
   * The current theme
   * @var string
   */
  private $theme;

  private $menuList;

  protected function init() {
    // Set default settings
    $this->m->Configuration->setDefault('theme.name', 'arachis');

    // Create meta-tags
    if (!$this->m->Templates->hideIdentity()) {
      $this->m->Templates->insertMeta(
        'generator',
        'PeanutCMS' . ($this->m->Templates->hideVersion() ? '' : ' ' . PEANUT_VERSION)
      );
    }
    if ($this->m->Configuration->exists('site.description')) {
      $this->m->Templates->insertMeta(
        'description',
        $this->m->Configuration->get('site.description')
      );
    }

    // Find and load theme
    if ($this->load()) {
      $this->m->Templates->setTheme(THEMES . $this->theme . '/');
    }
    else {
      new GlobalWarning(tr('Please install a theme'), 'theme-missing');
    }
  }

  /**
   * Find and load theme
   *
   * @return bool False if no theme could be loaded
   */
  private function load() {
    if ($this->m->Configuration->exists('theme.name')) {
      $theme = $this->m->Configuration->get('theme.name');
      if (file_exists(p(THEMES . $theme . '/themeinfo'))) {
        $this->theme = $theme;
        return TRUE;
      }
    }
    if (!is_dir(p(THEMES))) {
      return FALSE;
    }
    $dir = opendir(p(THEMES));
    if ($dir) {
      while (($theme = readdir($dir)) !== false) {
        if (is_dir(p(THEMES . $theme)) AND $theme != '.' AND $theme != '..') {
          if (file_exists(p(THEMES . $theme . '/themeinfo'))) {
            $this->m->Configuration->set('theme', $theme);
            $this->theme = $theme;
            return TRUE;
          }
        }
      }
      closedir($dir);
    }
    return FALSE;
  }


}

