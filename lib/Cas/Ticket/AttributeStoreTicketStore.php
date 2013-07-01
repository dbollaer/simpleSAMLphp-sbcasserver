<?php

class sspmod_sbcasserver_Cas_Ticket_AttributeStoreTicketStore extends sspmod_sbcasserver_Cas_Ticket_TicketStore
{
    private $attributeStoreUrl;
    private $attributeStoreDeleteUrl;
    private $attributeStorePrefix;

    public function __construct($config)
    {
        parent::__construct($config);

        $storeConfig = $config->getValue('ticketstore');

        if (!is_string($storeConfig['attributeStoreUrl'])) {
            throw new Exception('Missing or invalid attributeStoreUrl option in config.');
        }

        if (!is_string($storeConfig['attributeStorePrefix'])) {
            throw new Exception('Missing or invalid attributeStorePrefix option in config.');
        }

        $this->attributeStoreUrl = preg_replace('/\/$/', '', $storeConfig['attributeStoreUrl']);
        $this->attributeStoreDeleteUrl = preg_replace('/\/$/', '', $storeConfig['attributeStoreDeleteUrl']);
        $this->attributeStorePrefix = $storeConfig['attributeStorePrefix'];
    }

    public function getTicket($ticketId)
    {
        $scopedTicketId = $this->scopeTicketId($ticketId);

        return $this->getTicketFromAttributeStore($scopedTicketId);
    }

    public function addTicket($ticket)
    {
        $scopedTicketId = $this->scopeTicketId($ticket['id']);

        $this->addTicketToAttributeStore($scopedTicketId, $ticket);
    }

    public function deleteTicket($ticketId)
    {
        $scopedTicketId = $this->scopeTicketId($ticketId);

        $this->removeTicketFromAttributeStore($scopedTicketId);
    }

    private function getTicketFromAttributeStore($scopedTicketId)
    {
        $getParameters = array('http' => array('method' => 'GET', 'header' => array('Content-Type: application/json'),
            'ignore_errors' => true));

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: looking up ticket: ' . var_export($scopedTicketId, TRUE));

        $getUrl = $this->attributeStoreUrl . '/' . urlencode($scopedTicketId);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: get url: ' . var_export($getUrl, TRUE));

        $context = stream_context_create($getParameters);
        $response = file_get_contents($getUrl, false, $context);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: response: ' . var_export($response, TRUE));

        if (!is_null($response && $response != '')) {
            $attribute = json_decode($response, true);

            SimpleSAML_Logger::debug('AttributeStoreTicketStore: content: ' . var_export($attribute[0], TRUE));

            return json_decode($attribute[0]['value'], true);
        } else {
            return null;
        }
    }

    private function addTicketToAttributeStore($scopedTicketId, $content)
    {
        $expireInMinutes = ($content['validBefore'] - time()) % 60 + 1;

        $attribute = array('key' => $scopedTicketId, 'value' => json_encode($content), 'expireInMinutes' => $expireInMinutes);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: adding ticket: ' . var_export($scopedTicketId, TRUE) . ' with content: ' . var_export($content, TRUE));

        $postParameters = array('http' => array('method' => 'POST', 'header' => array('Content-Type: application/json'),
            'content' => json_encode($attribute), 'ignore_errors' => true));

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: posting: ' . var_export($postParameters, TRUE));

        $context = stream_context_create($postParameters);
        $response = file_get_contents($this->attributeStoreUrl, false, $context);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: response: ' . var_export($response, TRUE));

        return $response;
    }

    private function removeTicketFromAttributeStore($scopedTicketId)
    {
        $deleteParameters = array('http' => array('method' => 'DELETE', 'header' => array('Content-Type: application/json'),
            'ignore_errors' => true));

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: remove ticket: ' . var_export($scopedTicketId, TRUE));

        $deleteUrl = $this->attributeStoreDeleteUrl . '/' . urlencode($scopedTicketId);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: delete url: ' . var_export($deleteUrl, TRUE));

        $context = stream_context_create($deleteParameters);
        $response = file_get_contents($deleteUrl, false, $context);

        SimpleSAML_Logger::debug('AttributeStoreTicketStore: response: ' . var_export($response, TRUE));
    }

    private function scopeTicketId($ticketId)
    {
        return urlencode($this->attributeStorePrefix . '.' . $ticketId);
    }
}

?>