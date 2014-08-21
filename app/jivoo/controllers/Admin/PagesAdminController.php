<?php
class PagesAdminController extends AdminController {
  
  protected $models = array('Page');

  public function before() {
    parent::before();
    $this->Filtering->addPrimary('title');
  }
  
  public function index() {
    $this->title = tr('Pages');
    $this->pages = $this->Page;
    return $this->render();
  }
  public function add() {
    $this->title = tr('Add page');
    if ($this->request->hasValidData('Page')) {
      $data = $this->request->data['Page'];
      $data['published'] = isset($data['published']);
      $this->page = $this->Page->create($data);
      if ($this->page->save()) {
        $this->session->flash['success'][] = tr(
          'Page saved. %1',
          $this->Html->link(tr('Click here to view.'), $this->page)
        );
        if (isset($this->request->data['save-close']))
          return $this->redirect('index');
        else if (isset($this->request->data['save-new']))
          return $this->refresh();
        return $this->redirect(array('action' => 'edit', $this->page->id));
      }
    }
    else {
      $this->page = $this->Page->create();
    }
    return $this->render();
  }
  
//   public function add() {
//     $this->title = tr('Add page');
//     $this->ContentForm->doAdd($this->Page, tr('Page saved.'));
//     return $this->render();
//   }
  
  public function edit($pageId) {
    $this->title = tr('Edit page');
    $this->page = $this->Page->find($pageId);
    if ($this->page and $this->request->hasValidData('Page')) {
      $data = $this->request->data['Page'];
      $data['published'] = isset($data['published']);
      $this->page->addData($data);
      if ($this->page->save()) {
        $this->session->flash['success'][] = tr(
          'Page saved. %1',
          $this->Html->link(tr('Click here to view.'), $this->page)
        );
        if (isset($this->request->data['save-close']))
          return $this->redirect('index');
        else if (isset($this->request->data['save-new']))
          return $this->redirect('add');
        return $this->refresh();
      }
    }
    return $this->render('admin/pages/add.html');
  }

  public function delete($pageIds = null) {
    $this->ContentAdmin->makeSelection($this->Page, $pageIds);
    if (isset($this->ContentAdmin->selection)) {
      if ($this->request->hasValidData()) {
        $this->ContentAdmin->selection->delete();
        //...
      }
      //...
    }
    else {
      $this->page = $this->ContentAdmin->record;
      if ($this->page and $this->request->hasValidData()) {
        $this->page->delete();
        //...
      }
      //...
    }
  }
}
