<?php
/**
 * FauxAPI
 *  - A REST API interface for pfSense to facilitate dev-ops.
 *  - https://github.com/ndejong/pfsense_fauxapi
 * 
 * Copyright 2016 Nicholas de Jong  
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

include_once('util.inc');
include_once('guiconfig.inc');

$fauxapi_credentials_ini_file = '/etc/fauxapi/credentials.ini';

$pgtitle = array(gettext('System'), gettext('FauxAPI'), gettext('Credentials'));
include_once('head.inc');

$tab_array   = array();
$tab_array[] = array(gettext("Credentials"), true, "/fauxapi/admin/credentials.php");
$tab_array[] = array(gettext("About"), false, "/fauxapi/admin/about.php");
display_top_tabs($tab_array, true);

function fauxapi_load_credentials_ini($filename) {
    $ini_credentials = parse_ini_file($filename, TRUE);
    $credentials = array();
    foreach($ini_credentials as $ini_section => $ini_section_items) {
        if(isset($ini_section_items['secret'])) {
            if(!isset($ini_section_items['permit'])) {
                $ini_section_items['permit'] = '&lt;none&gt;';
            }
            $credentials[] = array(
                'apikey' => $ini_section,
                'permits' => explode(',',str_replace(' ', '', $ini_section_items['permit'])),
                'apiowner' => array_key_exists('owner', $ini_section_items) ? $ini_section_items['owner'] : '-'
            );
        }
    }
    return $credentials;
}

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">FauxAPI credentials</h2>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-compact sortable-theme-bootstrap" data-sortable>
                <thead>
                    <tr>
                        <th>key</th>
                        <th>secret</th>
                        <th>permits</th>
                        <th>owner</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach(fauxapi_load_credentials_ini($fauxapi_credentials_ini_file) as $credential) {
                        print '<tr>';
                        print '<td><div style="font-family:monospace;">'.$credential['apikey'].'</div></td>';
                        print '<td><div style="font-family:monospace;">[hidden]</div></td>';
                        print '<td><div style="font-family:monospace;">';
                        foreach($credential['permits'] as $permit){
                            print $permit.'<br />';
                        } 
                        print '</div></td>';
                        print '<td><div style="font-family:monospace;">'.$credential['apiowner'].'</div></td>';
                        print '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
    include('foot.inc');
?>