<?php


// src/Service/HelloService.php
namespace App\Service;

class SendsmsService
{
  
    public function __construct()
    {
    }

    public function sendsms($user)

    {
      return 'Hello, '.$user;    }
}