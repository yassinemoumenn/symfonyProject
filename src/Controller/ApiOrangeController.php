<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\Request;
use Qipsius\TCPDFBundle\Controller\TCPDFController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use App\Repository\CourrierRepository;
//require 'vendor/autoload.php';
use \Osms\Osms;

class ApiOrangeController extends AbstractController
{
    /**
     * @Route("/api/apiorange", name="apiorange",methods={"POSt"})
     */
public function index(Request $req,CourrierRepository $courrier,Container $container): Response
{
	echo "orange test ";
	$config = array(
    'clientId' => 'v1mbxYoDJLGbYSsLN06biuVUAmGHmkQD',
    'clientSecret' => '336029tXGiCuDTGP'
);
$osms = new Osms($config);
try{
	$response2 = $osms->getTokenFromConsumerKey();
	echo serialize($response2);
	
}
catch(Exception $e){
	    echo 'Exception reçue : ',  $e->getMessage(), "\n";
}
$response = $osms->getTokenFromConsumerKey();

//echo $response['access_token'];
if (!empty($response['access_token'])) {
    $senderAddress = 'tel:+212676969801';
    $receiverAddress = 'tel:+212682962462';
    $message = 'Hello World!';
    $senderName = 'Optimus Prime';
    $osms->sendSMS($senderAddress, $receiverAddress, $message, $senderName);
} else {
    // error
	echo "\nerreur api orange";
}
try{
	 $senderAddress = 'tel:+212676969801';
    $receiverAddress = 'tel:+212682962462';
    $message = 'Hello World!';
    $senderName = 'Optimus Prime';
    $osms->sendSMS($senderAddress, $receiverAddress, $message, $senderName);
	echo "trryryy";
}
catch (Exception $e) {
    echo 'Exception reçue : ',  $e->getMessage(), "\n";
}
return $this->render('toto/index.html.twig', [
    'controller_name' => 'TotoController',
]);
    }
    public  function doothat(Request $req){
       // file_put_contents($vcf_file_name, $str_contacts);

    }
    
    

}


