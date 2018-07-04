<?php

use Phalcon\Mvc\Model;

class Followers extends Model
{
    public $id;
    public $user_id;
    public $following_id;
}