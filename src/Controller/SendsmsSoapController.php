<?php
// src/Controller/HelloServiceController.php
namespace App\Controller;

use App\Service\SendsmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SendsmsController extends AbstractController
{
    /**
     * @Route("/soap")
     */
    public function index(SendsmsService $sendsmsService)
    {
        $soapServer = new \SoapServer('http://127.0.0.1:8000/Sendsms.wsdl');
        $soapServer->setObject($sendsmsService);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}