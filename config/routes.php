<?php
return array(
  Route::root('Posts::index'),
  Route::error('App::notFound'),
  Route::match('posts', 'Posts::index'),
  Route::match('tags', 'Posts::tagIndex'),
  Route::match('tags/*', 'Posts::viewTag'),
  Route::match('feed/posts.rss', 'Posts::feed'),
);
