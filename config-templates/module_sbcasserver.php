<?php
/* 
 * Configuration for the module sbcasserver.
 * 
 */

$config = array(

    'legal_service_urls' => array( //Any service url string matching any of the following prefixes is accepted
        'http://host1.domain:1234/path1',
        'https://host2.domain:5678/path2/path3',
    ),

    'legal_target_service_urls' => array( //Any target service url string matching any of the following prefixes is accepted
        'http://host3.domain:4321/path4',
        'https://host4.domain:8765/path5/path6',
    ),

    'ticketstore' => array( //defaults to filesystem ticket store using the directory 'ticketcache'
        'class' => 'sbcasserver:FileSystemTicketStore', //Not intended for production
        'directory' => 'ticketcache',

        //'class' => 'sbcasserver:MemCacheTicketStore',
        //'prefix' => 'some_prefix',
    ),

    'attrname' => 'eduPersonPrincipalName', // 'eduPersonPrincipalName',
    'attributes' => TRUE, // enable transfer of attributes

    'authsource' => 'casserver',
    'base64attributes' => true,
    'enable_logout' => true,
    'skip_logout_page' => true, //perform a redirect instead of showing a logout page with a link to the location given in the url parameter

    'service_ticket_expire_time' => 5, //how many seconds service tickets are valid for
    'proxy_granting_ticket_expire_time' => 600, //how many seconds proxy granting tickets are valid for at most
    'proxy_ticket_expire_time' => 5, //how many seconds proxy tickets are valid for
);

?>
