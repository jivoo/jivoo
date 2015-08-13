<?php
namespace Chat\Snippets;

use Jivoo\Snippets\SnippetBase;
use Jivoo\Core\Json;

class SendMessage extends SnippetBase {
  protected $helpers = array('Form');
  
  protected $models = array('Message');
  
  protected $dataKey = 'Message';
  
  public function post($data) {
    if (!$this->request->accepts('json'))
      return $this->invalid();
    $message = $this->Message->create(
      $data, array('message')
    );
    if (preg_match('/^\/name (.+)/i', $message->message, $matches) === 1) {
      $this->session['name'] = trim($matches[1]);
      return Json::encodeResponse('success');
    }
    if (isset($this->session['name']))
      $message->author = $this->session['name'];
    if ($message->save()) {
      return Json::encodeResponse('success');
    }
    return Json::encodeResponse($message->getErors());
  }
  
  public function get() {
    $this->view->data->message = $this->Message->create();
    return $this->render();
  }
}
