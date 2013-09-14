<?php
/*
*    simpleSAMLphp-sbcasserver is a CAS 1.0 and 2.0 compliant CAS server in the form of a simpleSAMLphp module
*
*    Copyright (C) 2013  Bjorn R. Jensen
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*
* Incoming parameters:
*  service
*  renew
*  ticket
*  pgtUrl
*
*/

require_once 'urlUtils.php';

/* Load simpleSAMLphp, configuration and metadata */
$casconfig = SimpleSAML_Configuration::getConfig('module_sbcasserver.php');

/* Instantiate protocol handler */
$protocolClass = SimpleSAML_Module::resolveClass('sbcasserver:Cas20', 'Cas_Protocol');
$protocol = new $protocolClass($casconfig);

if (array_key_exists('service', $_GET) && array_key_exists('ticket', $_GET)) {
    $ticketId = sanitize($_GET['ticket']);
    $service = sanitize($_GET['service']);

    $forceAuthn = isset($_GET['renew']) && sanitize($_GET['renew']);

    try {
        $ticketStoreConfig = $casconfig->getValue('ticketstore', array('class' => 'sbcasserver:FileSystemTicketStore'));
        $ticketStoreClass = SimpleSAML_Module::resolveClass($ticketStoreConfig['class'], 'Cas_Ticket');
        $ticketStore = new $ticketStoreClass($casconfig);

        $ticketFactoryClass = SimpleSAML_Module::resolveClass('sbcasserver:TicketFactory', 'Cas_Ticket');
        $ticketFactory = new $ticketFactoryClass($casconfig);

        $serviceTicket = $ticketStore->getTicket($ticketId);

        if (!is_null($serviceTicket) && ($ticketFactory->isServiceTicket($serviceTicket) ||
                ($ticketFactory->isProxyTicket($serviceTicket) && $method == 'proxyValidate'))
        ) {
            $ticketStore->deleteTicket($ticketId);

            $usernameField = $casconfig->getValue('attrname', 'eduPersonPrincipalName');

            $attributes = $serviceTicket['attributes'];

            if (!$ticketFactory->isExpired($serviceTicket) && $serviceTicket['service'] == $service && (!$forceAuthn || $serviceTicket['forceAuthn']) &&
                array_key_exists($usernameField, $attributes)
            ) {
                $protocol->setAttributes($attributes);

                if (isset($_GET['pgtUrl'])) {
                    $sessionTicket = $ticketStore->getTicket($serviceTicket['sessionId']);

                    $pgtUrl = sanitize($_GET['pgtUrl']);

                    if (!is_null($sessionTicket) && $ticketFactory->isSessionTicket($sessionTicket) && !$ticketFactory->isExpired($sessionTicket)) {
                        $proxyGrantingTicket = $ticketFactory->createProxyGrantingTicket(array(
                            'attributes' => $attributes,
                            'forceAuthn' => false,
                            'proxies' => array_merge(array($service), $serviceTicket['proxies']),
                            'sessionId' => $serviceTicket['sessionId']));
                        try {
                            SimpleSAML_Utilities::fetch($pgtUrl . '?pgtIou=' . $proxyGrantingTicket['iou'] . '&pgtId=' . $proxyGrantingTicket['id']);

                            $protocol->setProxyGrantingTicketIOU($proxyGrantingTicket['iou']);

                            $ticketStore->addTicket($proxyGrantingTicket);
                        } catch (Exception $e) {
                        }
                    }
                }

                echo $protocol->getValidateSuccessResponse($attributes[$usernameField][0]);
            } else {
                if ($ticketFactory->isExpired($serviceTicket)) {
                    echo $protocol->getValidateFailureResponse('INVALID_TICKET', 'Ticket: ' . $ticketId . ' expired');
                } else if ($serviceTicket['service'] != $service) {
                    echo $protocol->getValidateFailureResponse('INVALID_SERVICE', 'Expected: ' . $serviceTicket['service'] . ' was: ' . $service);
                } else if ($serviceTicket['forceAuthn'] != $forceAuthn) {
                    echo $protocol->getValidateFailureResponse('INVALID_TICKET', 'Service was issue from single sign on sesion: ');
                } else {
                    SimpleSAML_Logger::debug('sbcasserver:' . $method . ': internal server error. Missing user name attribute: ' .
                    var_export($usernameField, TRUE));

                    echo $protocol->getValidateFailureResponse('INTERNAL_ERROR', 'Missing user name attribute: ' . $usernameField . ' not found.');
                }
            }
        } else if (is_null($serviceTicket)) {
            echo $protocol->getValidateFailureResponse('INVALID_TICKET', 'ticket: ' . $ticketId . ' not recognized');
        } else if ($ticketFactory->isProxyTicket($serviceTicket) && $method == 'serviceValidate') {
            echo $protocol->getValidateFailureResponse('INVALID_TICKET', 'Ticket: ' . $ticketId . ' is a proxy ticket. Use proxyValidate instead.');
        } else {
            SimpleSAML_Logger::debug('sbcasserver:serviceValidate: internal server error. ' .
            var_export($e->getMessage(), TRUE));

            echo $protocol->getValidateFailureResponse('INVALID_TICKET', 'ticket: ' . $ticketId . ' is not a service ticket');
        }

    } catch (Exception $e) {
        SimpleSAML_Logger::debug('sbcasserver:serviceValidate: internal server error. ' . var_export($e->getMessage(), TRUE));

        echo $protocol->getValidateFailureResponse('INTERNAL_ERROR', $e->getMessage());
    }
} else if (!array_key_exists('service', $_GET)) {
    echo $protocol->getValidateFailureResponse('INVALID_REQUEST', 'Missing service parameter: [service]');
} else {
    echo $protocol->getValidateFailureResponse('INVALID_REQUEST', 'Missing ticket parameter: [ticket]');
}
?>