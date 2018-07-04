<?php

use Phalcon\Mvc\Model;

class Posts extends Model
{
    public $id;
    public $user_id;
    public $email;
    public $content;
    public $created_at;
}