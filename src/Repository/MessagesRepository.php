<?php
namespace App\Controller;

class MessagesRepository extends ServiceEntityRepository
{

    public function __construct()
    {
    }

    public function hello($name)
    {
        $message = (new \Swift_Message('Hello Service'))
            ->setTo('me@example.com')
            ->setBody($name.' says hi!');

        $this->mailer->send($message);

        return 'Hello, '.$name;
    }
}