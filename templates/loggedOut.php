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

assert('is_string($this->data["url"])');

$this->data['header'] = $this->t('{sbcasserver:sbcasserver:loggedout_header}');

$this->includeAtTemplateBase('includes/header.php');
?>

    <p>
        <?php
        echo $this->t('{sbcasserver:sbcasserver:loggedout_description}')
        ?>
    </p>

    <p>
        <a href="<?php echo($this->data["url"]) ?>"><?php echo($this->t('{sbcasserver:sbcasserver:continue_heading}')) ?></a>
    </p>

<?php

$this->includeAtTemplateBase('includes/footer.php');

if (isset($this->data['autofocus'])) {
    echo '<script type="text/javascript">window.onload = function() {document.getElementById(\'' . $this->data['autofocus'] . '\').focus();}</script>';
}