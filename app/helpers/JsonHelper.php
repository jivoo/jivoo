<?php

class JsonHelper extends ApplicationHelper {
  public function respond($response = NULL) {
    $template = new Template($this->m->Templates, $this->m->Routes);
    if (isset($response)) {
      $template->json = json_encode($response);
    }
    else {
      $data = $this->controller->getData();
      $template->json = json_encode($data);
    }
    $template->render('default.json');
  }
}