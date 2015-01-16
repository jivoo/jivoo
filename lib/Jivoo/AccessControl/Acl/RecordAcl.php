<?php
/**
 * An access control list implementation that calls the method 'hasPermission'
 * on the requesting user to check permissions. To use this ACL module,
 * implement a 'hasPermission' record method in the user model.
 * @package Jivoo\AccessControl\Acl
 */
class RecordAcl extends LoadableAcl {
  /**
   * {@inheritdoc}
   */
  public function hasPermission(IRecord $user = null, $permission) {
    if (!isset($user))
      return false;
    return $user->hasPermission($permission);
  }
  
}