<?php
class AdminMenu extends AppListener {
  
  protected $handlers = array('afterLoadModules');

  public function afterLoadModules() {
    $menu = new IconMenu(tr('Main'));
    $menu->fromArray(array(
      'status' => IconMenu::menu(tr('Status'), null, null, array(
        IconMenu::item(tr('Dashboard'), 'Admin::dashboard', 'meter'),
        IconMenu::item(tr('Install updates'), null, 'download3', '3'),
      )),
      'content' => IconMenu::menu(tr('Content'), null, null, array(
        'posts' => IconMenu::menu(tr('Posts'), 'Admin::Posts', 'newspaper', array(
          IconMenu::item(tr('Posts'), 'Admin::Posts::index'),
          IconMenu::item(tr('Tags'), null),
        )),
        'pages' => IconMenu::item(tr('Pages'), 'Admin::Pages', 'file'),
        'comments' => IconMenu::item(tr('Comments'), 'Admin::Comments', 'bubbles'),
      )),
      'appearance' => IconMenu::menu(tr('Appearance'), null, null, array(
      )),
      'settings' => IconMenu::menu(tr('Settings'), array(), null, array(
        'extensions' => IconMenu::item(tr('Extensions'), 'Admin::Extensions', 'powercord'),
        'users' => IconMenu::menu(tr('Users'), 'Admin::Users', 'users', array(
          IconMenu::item(tr('Users'), 'Admin::Users::index'),
          IconMenu::item(tr('Groups'), null),
        )),
      )),
      'about' => IconMenu::menu(tr('About'), array(), null, array(
        IconMenu::item(tr('Help & support'), null, 'support'),
        IconMenu::item(tr('About Jivoo'), 'Admin::about', 'jivoo'),
      )),
    ));
    $this->m->Administration->menu['main'] = $menu;
    $this->m->Administration->menu['shortcuts'] = IconMenu::menu(
      tr('Shortcuts'), null, null, array(
        IconMenu::item($this->config['Templates']['title'], null, 'home'),
        IconMenu::item(tr('Dashboard'), 'Admin::dashboard', 'meter'),
        IconMenu::menu(tr('Add'), array('fragment' => ''), 'plus', array(
          IconMenu::item(tr('Add post'), 'Admin::Posts::add'),
          IconMenu::item(tr('Add page'), 'Admin::Pages::add'),
          IconMenu::item(tr('Add comment'), 'Admin::Comments::add'),
        )),
      )
    );
  }
}
