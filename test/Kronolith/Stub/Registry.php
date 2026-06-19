<?php

class Kronolith_Stub_Registry extends Horde_Test_Stub_Registry
{
    public $admin = false;

    public function isAdmin(array $options = [])
    {
        return $this->admin;
    }

    public function pushApp($app)
    {
        return false;
    }

    public function setAuth($user, $credentials = [])
    {
        $this->_user = $user;
    }

    public function getAuthCredential($credential = null, $app = null)
    {
        if (!$this->_user) {
            return false;
        }

        return is_null($credential) ? [] : false;
    }
}
