import requests
from suds.client import Client




wsdlURL="http://127.0.0.1:8000/Sendsms.wsdl"
client_soap_Sendsms=Client(wsdlURL)
resultatSoap=client_soap_Sendsms.service.Sendsms("yassine")
print(resultatSoap)