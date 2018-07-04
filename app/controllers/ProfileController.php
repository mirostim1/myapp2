<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

class ProfileController extends ControllerBase
{
    public function beforeExecuteRoute($dispatcher)
    {
        $restricted = [
            'index',
            'myposts',
            'deletemyPost',
            'allposts',
            'newpost',
            'allusers',
            'userfollowers',
            'follow',
            'unfollow',
            'savepost',
            'signout'
        ];

        $logged_in = $this->session->get('logged_in');

        if(in_array($dispatcher->getActionName(), $restricted) && !$logged_in) {
            $dispatcher->forward(
                [
                    'controller' => 'index',
                    'action'     => 'index',
                ]
            );

            return false;
        }
    }

    public function indexAction()
    {
        $email = $this->session->get('email');
        $userId = $this->session->get('user_id');

        $this->view->setVar('email', $email);
        $this->view->setVar('user_id', $userId);

        $followingIds = $this->userFollowersAction();
        $this->view->setVar('followingIds', $followingIds);

        $allUser = $followingIds;
        array_push($allUser, $userId);

        $posts = Posts::find([
            'user_id IN ({user_id:array})',
            'bind' => ['user_id' => $allUser],
            'order' => 'created_at DESC'
        ]);

        $currentPage = (int) $_GET['page'];
        !$currentPage ? $currentPage = '' : '';

        $paginator = new PaginatorModel(
            [
                'data'  => $posts,
                'limit' => 5,
                'page'  => $currentPage,
            ]
        );
        $page = $paginator->getPaginate();

        $this->view->setVar('page', $page);
    }

    public function myPostsAction()
    {
        $email = $this->session->get('email');
        $userId = $this->session->get('user_id');

        $this->view->setVar('email', $email);
        $this->view->setVar('user_id', $userId);

        $posts = Posts::find(
            [
                'email = :email:',
                'bind' => ['email' => $this->session->get('email')],
                'order' => 'created_at DESC'
            ]
        );

        $currentPage = (int) $_GET['page'];
        !$currentPage ? $currentPage = 1: '';

        $paginator = new PaginatorModel(
            [
                'data'  => $posts,
                'limit' => 5,
                'page'  => $currentPage,
            ]
        );
        $page = $paginator->getPaginate();

        $this->view->setVar('page', $page);
    }

    public function deleteMyPostAction()
    {
        try {
            if($this->request->getPost('post_id')) {
                $post = Posts::findFirst(
                    [
                        'id = :id:',
                        'bind' => [
                            'id' => $this->request->getPost('post_id')
                        ]
                    ]
                );
            }

            if($this->request->getPost('id')) {
                $post = Posts::findFirst(
                    [
                        'id = :id:',
                        'bind' => [
                            'id' => $this->request->getPost('id')
                        ]
                    ]
                );
            }

            $delete = $post->delete();
        } catch(\Exception $e) {
            echo 'Error during deleting post from DB';
        }

        if($delete) {
            $this->flashSession->message('succ', 'You have successfully deleted post.');
        } else {
            $this->flashSession->message('err', 'Error happened, please try again.');
        }

        if($this->request->getPost('post_id')) {
            return $this->response->redirect('profile/myposts');
        } elseif($this->request->getPost('id')) {
            return $this->response->redirect('profile/allposts');
        }
    }

    public function allPostsAction() {
        $email = $this->session->get('email');
        $userId = $this->session->get('user_id');

        $this->view->setVar('email', $email);
        $this->view->setVar('user_id', $userId);

        $posts = Posts::find([
            'order' => 'created_at DESC'
        ]);

        $currentPage = (int) $_GET['page'];
        !$currentPage ? $currentPage = 1: '';

        $paginator = new PaginatorModel(
            [
                'data'  => $posts,
                'limit' => 5,
                'page'  => $currentPage,
            ]
        );
        $page = $paginator->getPaginate();

        $this->view->setVar('page', $page);
    }

    public function newPostAction()
    {
        $email = $this->session->get('email');
        $userId = $this->session->get('user_id');

        $this->view->setVar('email', $email);
        $this->view->setVar('user_id', $userId);
    }

    public function allUsersAction()
    {
        $email = $this->session->get('email');
        $userId = $this->session->get('user_id');

        $this->view->setVar('email', $email);
        $this->view->setVar('user_id', $userId);

        $users = Users::find([
            'order' => 'id DESC'
        ]);
        $this->view->setVar('users', $users);

        $followingIds = $this->userFollowersAction();

        $this->view->setVar('followingIds', $followingIds);
    }

    public function userFollowersAction()
    {
        $followers = Followers::find(
            [
                'user_id = :user_id:',
                'bind' => ['user_id' => $this->session->get('user_id')]
            ]
        );

        $followingIds = [];
        foreach($followers as $follower) {
            array_push($followingIds, $follower->following_id);
        }

        return $followingIds;
    }

    public function savePostAction()
    {
        $validation = new Validation();

        $validation->add(
            'content',
            new StringLength(
                [
                    "max"            => 255,
                    "min"            => 5,
                    "messageMaximum" => "Post content can't be greater then 255 chars",
                    "messageMinimum" => 'Post content must be at least 5 chars long'
                ]
            )
        );

        $validation->add(
            'content',
            new PresenceOf(['message' => 'Content of post is required'])
        );

        $validation->add(
            'email',
            new Email(['message' => 'Email field must be properly formated'])
        );

        $validation->add(
            'email',
            new PresenceOf(['message' => 'Email is required'])
        );

        $messages = $validation->validate($_POST);

        if(count($messages)) {
            $errorMsg = [];
            foreach($messages as $message) {
                array_push($errorMsg, $message->getMessage());
            }
        }

        if($this->request->getPost('email') != $this->session->get('email')) {
            $emailsNotMatch = "Email must be your registration email address";
            if(!isset($errorMsg)) {
                $errorMsg = [];
            }
            array_push($errorMsg, $emailsNotMatch);
        }

        if(!$errorMsg) {
            $post = new Posts();

            $post->user_id = $this->session->get('user_id');
            $post->email = $this->request->getPost('email');
            $post->content = $this->request->getPost('content');
            $post->created_at = date('Y-m-d H:i:j', time());

            try {
                $success = $post->save();
            } catch (\Exception $e) {
                echo 'Error happened during saving data to DB';
            }

            if($success) {
                $this->flashSession->message('succ', 'New post has been successfully posted');
            }
        } else {
            $this->flashSession->message('err', $errorMsg[0]);
        }

        return $this->response->redirect('profile/newpost');
    }

    public function unfollowAction()
    {
        try {
            $follower = Followers::findFirst(
                [
                    'user_id = :user_id: AND following_id = :following_id:',
                    'bind' => [
                        'user_id' => $this->session->get('user_id'),
                        'following_id' => $this->request->getPost('unfollowbtn')
                    ]
                ]
            );

            $delete = $follower->delete();
        } catch(\Exception $e) {
            echo 'Error during deleting follower data';
        }

        if($delete) {
            $this->flashSession->message('succ', 'You have successfully deleted user from list of following.');
        } else {
            $this->flashSession->message('err', 'Error happened, please try again.');
        }
        return $this->response->redirect('profile/allusers');
    }

    public function followAction()
    {
        $follower = new Followers();
        $follower->user_id = $this->session->get('user_id');
        $follower->following_id = $this->request->getPost('followbtn');

        try {
            $success = $follower->save();
        } catch(\Exception $e) {
            echo 'Error during saving to DB new follower data';
        }

        if($success) {
            $this->flashSession->message('succ', 'You have added new user to follow.');
        } else {
            $this->flashSession->message('err', 'Error happened, please try again.');
        }
        return $this->response->redirect('profile/allusers');
    }

    public function signoutAction()
    {
        $this->session->remove('logged_in');
        $this->session->remove('email');
        $this->session->remove('is_admin');
        $this->session->remove('user_id');
        $this->flashSession->message('succ', 'Succesfully logged out.');

        return $this->response->redirect();
    }
}