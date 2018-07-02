<?php 

 //blah blah code

 include('VPCPaymentConnection.php');
 $conn = new VPCPaymentConnection();
 // This is secret for encoding the SHA256 hash
 // This secret will vary from merchant to merchant

 $secureSecret = 'EB231ECFCEFE6F29BCD5E31EE458080F';
 // Set the Secure Hash Secret used by the VPC connection object
 $conn->setSecureSecret($secureSecret);
 $vpcURL = $this->virtualPaymentClientURL;
 $title = $this->title;

 //add all fields 
 $conn->addDigitalOrderField('vpc_Version', '1');
 $conn->addDigitalOrderField('vpc_Command', 'pay');
 $conn->addDigitalOrderField('vpc_AccessCode', '21C482B1');
 $conn->addDigitalOrderField('vpc_MerchTxnRef', 'ps');
 $conn->addDigitalOrderField('vpc_Merchant', 'TESTPURPLESUNUSD');
 $conn->addDigitalOrderField('vpc_OrderInfo', 'dd');
 $conn->addDigitalOrderField('vpc_Amount', '1');
 $conn->addDigitalOrderField('vpc_ReturnURL', 'https://www.axongre.com');
 $conn->addDigitalOrderField('vpc_Locale', 'en_US');
 $conn->addDigitalOrderField('vpc_Currency', 'USD');

 // Add original order HTML so that another transaction can be attempted.
 $conn->addDigitalOrderField("AgainLink", $againLink);
 // Obtain a one-way hash of the Digital Order data and add this to the Digital Order
 $secureHash = $conn->hashAllFields();
 $conn->addDigitalOrderField("Title", $title);
 $conn->addDigitalOrderField("vpc_SecureHash", $secureHash);
 $conn->addDigitalOrderField("vpc_SecureHashType", "SHA256");

 // Obtain the redirection URL and redirect the web browser
 $vpcURL = $conn->getDigitalOrder($vpcURL);
 header("Location: ".$vpcURL);





?>