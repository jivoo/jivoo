<?php
class PostsAdminController extends AdminController {
  
  protected $models = array('Post');
  
  public function index() {
    $this->title = tr('All posts');
    $this->posts = $this->Post;
    return $this->render();
  }
  public function add() {
    $this->title = tr('Add post');
    if ($this->request->hasValidData('Post')) {
      $this->post = $this->Post->create($this->request->data['Post']);
      $this->post->user = $this->user;
      if ($this->post->save()) {
        $this->session->flash['success'][] = tr(
          'Post saved. %1',
          $this->Html->link(tr('Click here to view.'), $this->post)
        );
        if (isset($this->request->data['save-close']))
          return $this->redirect('index');
        else if (isset($this->request->data['save-new']))
          return $this->refresh();
        return $this->redirect(array('action' => 'edit', $this->post->id));
      }
    }
    else {
      $this->post = $this->Post->create();
      $this->post->commenting = $this->config['blog']['commentingDefault'];
    }
    return $this->render();
  }
  
  public function edit($postId) {
    $this->title = tr('Edit post');
    $this->post = $this->Post->find($postId);
    if ($this->post and $this->request->hasValidData('Post')) {
      $data = $this->request->data['Post'];
      $data['commenting'] = isset($data['commenting']);
      $this->post->addData($data);
      if ($this->post->save()) {
        $this->session->flash['success'][] = tr(
          'Post saved. %1',
          $this->Html->link(tr('Click here to view.'), $this->post)
        );
        if (isset($this->request->data['save-close']))
          return $this->redirect('index');
        else if (isset($this->request->data['save-new']))
          return $this->redirect('add');
        return $this->refresh();
      }
    }
    return $this->render('admin/posts/add.html');
  }
  
  public function publish($postId) {
    return $this->ContentAdmin->quickEdit(array(
      'record' => $this->Post->find($postId),
      'edit' => array('status' => 'published'),
      'confirm' => tr('Do you want to publish this post?'),
    ));
  }
  
  public function unpublish($postId) {
    return $this->ContentAdmin->quickEdit(array(
      'record' => $this->Post->find($postId),
      'edit' => array('status' => 'pending'),
      'confirm' => tr('Do you want to unpublish this post?')
    ));
  }
  
  public function delete($postId) {
    return $this->ContentAdmin->delete(array(
      'record' => $this->Post->find($postId),
      'confirm' => tr('Do you want to delete this post?'),
      'title' => tr('Delete post')
    ));
  }
}
