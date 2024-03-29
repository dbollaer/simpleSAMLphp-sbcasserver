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
*/

class sspmod_sbcasserver_Cas_Ticket_FileSystemTicketStore extends sspmod_sbcasserver_Cas_Ticket_TicketStore
{

    private $pathToTicketDirectory;

    public function __construct($config)
    {
        $storeConfig = $config->getValue('ticketstore', array('directory' => 'ticketcache'));

        if (!is_string($storeConfig['directory'])) {
            throw new Exception('Invalid directory option in config.');
        }

        $path = $config->resolvePath($storeConfig['directory']);

        if (!is_dir($path))
            throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');

        if (!is_writable($path))
            throw new Exception('Directory for CAS Server ticket storage [' . $path . '] is not writable. ');

        $this->pathToTicketDirectory = preg_replace('/\/$/', '', $path);
    }

    public function getTicket($ticketId)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticketId;

        if (file_exists($filename)) {
            $content = file_get_contents($filename);

            return unserialize($content);
        } else {
            return null;
        }
    }

    public function addTicket($ticket)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticket['id'];
        file_put_contents($filename, serialize($ticket));
    }

    public function deleteTicket($ticketId)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticketId;

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}

?>