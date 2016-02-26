<?php
include_once (dirname(__FILE__) . '/../settings.php');

$network = new \Oara\Network\Publisher\LinkShare\UK();
$credentials = $network->getNeededCredentials();
$credentials["user"] = "fubraltd";
$credentials["password"] = "Hj94rew6";
$network->login($credentials);
if ($network->checkConnection()){
    //$network->getPaymentHistory();
    $merchantList = $network->getMerchantList();
    $startDate = new \DateTime('2016-01-01');
    $endDate = new \DateTime('2016-01-31');
    $transactionList = $network->getTransactionList($merchantList, $startDate, $endDate);

} else {
    echo "Network credentials not valid \n";
}