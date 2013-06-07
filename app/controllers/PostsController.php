<?php

class PostsController extends AppController {

  protected $helpers = array('Html', 'Pagination', 'Form', 'Filtering',
    'Backend', 'Json', 'Bulk'
  );

  protected $modules = array('Editors');
  
  protected $models = array('Post', 'Comment', 'Tag');

  public function preRender() {
    $this->Filtering->addSearchColumn('title');
    $this->Filtering->addSearchColumn('content');
    $this->Filtering->addFilterColumn('status');
    $this->Filtering->addFilterColumn('date');

    $this->Filtering->addPredefined(tr('Published'), 'status:published');
    $this->Filtering->addPredefined(tr('Draft'), 'status:draft');

    $this->Pagination->setLimit(10);

    $this->Bulk
      ->addUpdateAction('publish', tr('Publish'),
        array('status' => 'published')
      );
    $this->Bulk
      ->addUpdateAction('conceal', tr('Conceal'), array('status' => 'draft'));

    $this->Bulk->addDeleteAction('delete', tr('Delete'));
  }

  public function index() {
    $select = SelectQuery::create()->where('status = "published"')
      ->orderByDescending('date');
    $this->Pagination->setCount($this->Post->count());

    $this->Pagination->paginate($select);

    $this->posts = $this->Post->all($select);

    $this->render();
  }

  public function view($post) {
    $this->reroute();

    $this->post = $this->Post->find($post);

    if (!$this->post
      OR ($this->post->status != 'published'
        AND !$this->Auth->hasPermission('backend.posts.viewDraft'))) {
      return $this->render('404.html');
    }

    $select = SelectQuery::create()->orderBy('date')
      ->where('status = "approved"');

    $this->Pagination->setLimit(10);

    $this->Pagination->setCount($this->post->countComments(clone $select));
    $this->Pagination->paginate($select);

    $this->user = $this->Auth->getUser();

    $this->Comment->setFieldEditor('content',
      $this->m->Editors->getEditor($this->config['comments']['editor'])
    );

    if ($this->Auth->hasPermission('frontend.posts.comments.add')) {
      if ($this->request->isPost() AND $this->request->checkToken()) {
        $this->newComment = $this->Comment->create($this->request->data['comment'],
          array('author', 'email', 'website', 'content')
        );
        if (!empty($this->newComment->website)
          AND preg_match('/^https?:\/\//', $this->newComment->website) == 0) {
          $this->newComment->website = 'http://' . $this->newComment->website;
        }
        if ($this->user) {
          $this->newComment->setUser($this->user);
          $this->newComment->author = $this->user->username;
          $this->newComment->email = $this->user->email;
        }
        $this->newComment->setPost($this->post);
        $this->newComment->ip = $this->request->ip;
        if ($this->config['commentApproval'] == 'on'
          AND !$this->Auth->hasPermission('backend.posts.comments.approve')) {
          $this->newComment->status = 'pending';
        }
        else {
          $this->newComment->status = 'approved';
        }
        if ($this->newComment->isValid()) {
          $this->newComment->save();
          $this->post->comments += 1;
          $this->post->save();
          $this->Pagination->setCount($this->post->comments);

          if (!empty($this->newComment->author)) {
            $this->request->cookies['comment_author'] = $this->newComment
              ->author;
          }
          if (!empty($this->newComment->email)) {
            $this->request->cookies['comment_email'] = $this->newComment->email;
          }
          if (!empty($this->newComment->website)) {
            $this->request->cookies['comment_website'] = $this->newComment
              ->website;
          }

          $this
            ->refresh(array('page' => $this->Pagination->getPages()),
              'comment' . $this->newComment->id
            );
        }
      }
      else {
        $this->newComment = $this->Comment->create();
        if (isset($this->request->cookies['comment_author'])) {
          $this->newComment->author = $this->request->cookies['comment_author'];
        }
        if (isset($this->request->cookies['comment_email'])) {
          $this->newComment->email = $this->request->cookies['comment_email'];
        }
        if (isset($this->request->cookies['comment_website'])) {
          $this->newComment->website = $this->request
            ->cookies['comment_website'];
        }
      }
    }

    $this->comments = $this->post->getComments($select);

    $this->title = $this->post->title;
    $this->render();
  }

  public function manage() {
    $this->Backend->requireAuth('backend.posts.manage');

    $select = SelectQuery::create()->orderByDescending('date');

    $this->Filtering->filter($select);

    if (isset($this->request->query['filter'])) {
      $this->Pagination->setCount($this->Post->count($select));
    }
    else {
      $this->Pagination->setCount($this->Post->count());
    }

    if ($this->Bulk->isBulk()) {
      if ($this->Bulk->isDelete()) {
        $query = SelectQuery::create();
      }
      else {
        $query = UpdateQuery::create();
      }
      $this->Filtering->filter($query);
      $this->Bulk->select($query);
      $this->Post->dataSource->execute($query);
      if (!$this->request->isAjax()) {
        $this->refresh();
      }
    }

    $this->Pagination->paginate($select);

    $this->posts = $this->Post->all($select);
    $this->title = tr('Manage posts');
    if ($this->request->isAjax()) {
      $html = '';
      foreach ($this->posts as $this->post) {
        $html .= $this->render('posts/post.html', true);
      }
      $this->Json->respond(array('html' => $html));
    }
    else {
      $this->returnToThis();
      $this->render();
    }
  }

  public function add() {
    $this->Backend->requireAuth('backend.posts.add');

    $examplePost = $this->Post->create();
    $examplePost->name = '%name%';
    $examplePost->date = time();
    $exampleLink = explode('%name%',
      $this->m->Routing->getLink($examplePost)
    );
    $examplePost = null;
    $this->nameInPermalink = count($exampleLink) >= 2;
    $this->beforePermalink = $exampleLink[0];
    $this->afterPermalink = $exampleLink[1];

    $this->Post->setFieldEditor('content',
      $this->m->Editors->getEditor($this->config['editor'])
    );

    if ($this->request->isPost()
      AND $this->request->checkToken('post')) {
      $this->post = $this->Post->create($this->request->data['post']);
      if (isset($this->request->data['publish'])) {
        $this->post->status = 'published';
      }
      else {
        $this->post->status = 'draft';
      }
      if ($this->post->isValid()) {
        $this->post->setUser($this->Auth->getUser());
        $this->post->save();
        if ($this->post->status == 'published') {
          $this->redirect($this->post);
        }
        else {
          $this->session->notice(tr('Post successfully created'));
          $this->refresh();
        }
      }
      else {
        foreach ($this->post->getErrors() as $field => $error) {
          $this->session
            ->alert(
              $this->Post->getFieldLabel($field) . ': ' . $error
            );
        }
      }
    }
    else {
      $this->post = $this->Post->create();
    }
    $this->title = tr('New post');
    $this->render('posts/edit.html');
  }

  public function edit($post) {
    $this->Backend->requireAuth('backend.posts.edit');

    $this->post = $this->Post->find($post);
    if (!$this->post) {
      return $this->notFound();
    }

    $this->Post->setFieldEditor('content',
      $this->m->Editors->getEditor($this->config['editor'])
    );

    if ($this->request->isPost()) {
      $this->post->addData($this->request->data['post']);
      if (isset($this->request->data['publish'])) {
        $this->post->status = 'published';
      }
      else if (!isset($this->request->data['post']['status'])) {
        $this->post->status = 'draft';
      }
      if ($this->post->isValid()) {
        $this->post->save();
        if (!$this->request->isAjax()) {
          $this->goBack();
          if ($this->post->status == 'published') {
            $this->redirect($this->post);
          }
          else {
            $this->session->notice(tr('Post successfully saved'));
            $this->refresh();
          }
        }
      }
      else {
        foreach ($this->post->getErrors() as $field => $error) {
          $this->session
            ->alert(
              $this->post->getFieldLabel($field) . ': ' . $error
            );
        }
      }
    }
    $examplePost = $this->Post->create();
    $examplePost->name = '%name%';
    $examplePost->date = time();
    $exampleLink = explode('%name%',
      $this->m->Routing->getLink($examplePost)
    );
    $examplePost = null;
    $this->nameInPermalink = count($exampleLink) >= 2;
    $this->beforePermalink = $exampleLink[0];
    $this->afterPermalink = $exampleLink[1];
    $this->title = tr('Edit post');
    if (!$this->request->isAjax()) {
      $this->render();
    }
    else {
      $this->Json
        ->respond(array('html' => $this->render('posts/post.html', true)));
    }
  }

  public function delete($post) {
    $this->Backend->requireAuth('backend.posts.delete');

    $this->render('not-implemented.html');
  }

  public function tagIndex() {
    $this->render('not-implemented.html');
  }

  public function viewTag($tag) {
    $this->tag = $this->Tag->first(SelectQuery::create()->where('name = ?', $tag));

    /** @todo This includes unpublished posts */
    $this->Pagination->setCount($this->tag->countPosts());

    $select = SelectQuery::create()->where('status = "published"')
      ->orderByDescending('date');

    $this->Pagination->paginate($select);

    $this->posts = $this->tag->getPosts($select);

    $this->title = $this->tag->tag;

    $this->render('posts/index.html');
  }

  public function manageTags() {
    $this->Backend->requireAuth('backend.tags.manage');
    $this->title = tr('Tags');
    $this->tags = $this->Tag->all();
    $this->render();
  }

}
