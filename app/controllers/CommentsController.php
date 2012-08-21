<?php

class CommentsController extends ApplicationController {
  
  protected $helpers = array('Html', 'Pagination', 'Form', 'Filtering', 'Backend');
  
  public function index($post) {
    $this->render('not-implemented.html');
  }
  
  public function view($post, $comment) {
    $this->render('not-implemented.html');
  }

  public function manage() {
    $this->Backend->requireAuth('backend.comments.manage');

    if ($this->request->isPost()) {
      var_dump($this->request->data);
      exit;
    }
  
    $select = SelectQuery::create()
    ->orderByDescending('date');
  
    $this->Filtering->addSearchColumn('content');
    $this->Filtering->addFilterColumn('status');
    $this->Filtering->addFilterColumn('author');
    $this->Filtering->addFilterColumn('date');
  
    $this->Filtering->filter($select);
  
    if (isset($this->request->query['filter'])) {
      $this->Pagination->setCount(Comment::count(clone $select));
    }
    else {
      $this->Pagination->setCount(Comment::count());
    }
  
    $this->Pagination->setLimit(10)->paginate($select);
  
    $this->comments = Comment::all($select);
    $this->title = tr('Comments');

    $this->accessToken = $this->request->getToken();
  
    $this->returnToThis();
    $this->render();
  }

  public function edit($comment = NULL) {
    $this->Backend->requireAuth('backend.comments.edit');

    if (isset($comment)) {
      $this->comment = Comment::find($comment);
    }

    if ($this->request->isPost() AND $this->request->checkToken()) {
      $this->comment->addData($this->request->data['comment']);
      $this->comment->save(array('validate' => FALSE));
    }
    if (!$this->request->isAjax()) {
      $this->goBack();
      $this->redirect(array('action' => 'comments'));
    }
  }

  public function delete($comment = NULL) {
  }
  
  public function approve($comment = NULL) {
    $this->Backend->requireAuth('backend.comments.approve');
  
    if ($this->request->isPost() AND $this->request->checkToken()) {
      if (isset($comment)) {
        $comment = Comment::find($comment);
        if ($comment) {
          $comment->status = 'approved';
          $comment->save(array('validate' => FALSE));
        }
      }
    }
    if (!$this->request->isAjax()) {
      $this->goBack();
      $this->redirect(array('action' => 'comments'));
    }
  }
}
