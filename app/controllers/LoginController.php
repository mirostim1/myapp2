<?php

use Phalcon\Mvc\Controller;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Uniqueness;
use Phalcon\Http\Response;

class LoginController extends ControllerBase
{
    public function indexAction()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = Users::findFirst(
            [
                'email = :email:',
                'bind' => ['email' => $email]
            ]
        );

        if($user) {
            if($this->security->checkHash($password, $user->password)) {
                $this->session->set('logged_in', 1);
                $this->session->set('is_admin', $user->is_admin);
                $this->session->set('email', $user->email);
                $this->session->set('user_id', $user->id);
                return $this->response->redirect('profile');
            } else {
                $this->flashSession->message('err', 'User with these credentials not exist. Please try again.');
                return $this->response->redirect();
            }
        } else {
            $this->flashSession->message('err', 'User with these credentials not exist. Please try again.');
            return $this->response->redirect();
        }

    }

    public function registerAction()
    {
        $user = new Users();
        $user->email = $this->request->getPost('email');
        $user->password = $this->security->hash($this->request->getPost('password'));

        try {
            $success = $user->save();
        } catch (\Exception $e) {
            echo 'Error happened during saving data to DB';
        }

        if($success) {
            $this->flashSession->message('succ', 'You have been successfully registered. You can now login.');
        } else {
            $this->flashSession->message('err', 'User has not been registered. Try again with different email entry.');
        }

        return $this->response->redirect();
    }
}