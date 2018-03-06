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

require_once('util.inc');
require_once('guiconfig.inc');

$pgtitle = array(gettext('System'), gettext('FauxAPI'), gettext('About'));
include_once('head.inc');

$tab_array   = array();
$tab_array[] = array(gettext("Credentials"), false, "/fauxapi/admin/credentials.php");
$tab_array[] = array(gettext("About"), true, "/fauxapi/admin/about.php");
display_top_tabs($tab_array, true);

?>

<div>
<!--READMESTART-->
<h1>
<a id="user-content-fauxapi---v12" class="anchor" href="#fauxapi---v12" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>FauxAPI - v1.2</h1>
<p>A REST API interface for pfSense 2.3+ to facilitate devops:-</p>
<ul>
<li><a href="https://github.com/ndejong/pfsense_fauxapi">https://github.com/ndejong/pfsense_fauxapi</a></li>
</ul>
<p>Additionally available are a set of <a href="#user-content-client_libraries">client libraries</a>
that hence make programmatic access and management of pfSense hosts for devops
tasks feasible.</p>
<hr>
<h3>
<a id="user-content-api-action-summary" class="anchor" href="#api-action-summary" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Action Summary</h3>
<ul>
<li>
<a href="#user-content-alias_update_urltables">alias_update_urltables</a> - Causes the pfSense host to immediately update any urltable alias entries from their (remote) source URLs.</li>
<li>
<a href="#user-content-config_backup">config_backup</a> - Causes the system to take a configuration backup and add it to the regular set of system change backups.</li>
<li>
<a href="#user-content-config_backup_list">config_backup_list</a> - Returns a list of the currently available system configuration backups.</li>
<li>
<a href="#user-content-config_get">config_get</a> - Returns the system configuration as a JSON formatted string.</li>
<li>
<a href="#user-content-config_reload">config_reload</a> - Causes the pfSense system to perform a reload of the <code>config.xml</code> file.</li>
<li>
<a href="#user-content-config_restore">config_restore</a> - Restores the pfSense system to the named backup configuration.</li>
<li>
<a href="#user-content-config_set">config_set</a> - Sets a full system configuration and (by default) reloads once successfully written and tested.</li>
<li>
<a href="#user-content-function_call">function_call</a> - Call directly a pfSense PHP function with API user supplied parameters.</li>
<li>
<a href="#user-content-gateway_status">gateway_status</a> - Returns gateway status data.</li>
<li>
<a href="#user-content-rule_get">rule_get</a> - Returns the numbered list of loaded pf rules from a <code>pfctl -sr -vv</code> command on the pfSense host.</li>
<li>
<a href="#user-content-send_event">send_event</a> - Performs a pfSense "send_event" command to cause various pfSense system actions.</li>
<li>
<a href="#user-content-system_reboot">system_reboot</a> - Reboots the pfSense system.</li>
<li>
<a href="#user-content-system_stats">system_stats</a> - Returns various useful system stats.</li>
</ul>
<hr>
<h3>
<a id="user-content-approach" class="anchor" href="#approach" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Approach</h3>
<p>At its core FauxAPI simply reads the core pfSense <code>config.xml</code> file, converts it
to JSON and returns to the API caller.  Similarly it can take a JSON formatted
configuration and write it to the pfSense <code>config.xml</code> and handles the required
reload operations.  The ability to programmatically interface with a running
pfSense host(s) is enormously useful however it should also be obvious that this
provides the API user the ability to create configurations that can break your
pfSense system.</p>
<p>FauxAPI provides easy backup and restore API interfaces that by default store
configuration backups on all configuration write operations thus it is very easy
to roll-back even if the API user manages to deploy a "very broken" configuration.</p>
<p>Multiple sanity checks take place to make sure a user provided JSON config will
correctly convert into the (slightly quirky) pfSense XML <code>config.xml</code> format and
then reload as expected in the same way.  However, because it is not a real
per-action application-layer interface it is still possible for the API caller
to create configuration changes that make no sense and can potentially disrupt
your pfSense system - as the package name states, it is a "Faux" API to pfSense
filling a gap in functionality with the current pfSense product.</p>
<p>A common source of confusion is the requirement to pass the <em>FULL</em> configuration
into the <strong>config_set</strong> action and not just the portion of the configuration you
wish to adjust.  A <strong>config_patch</strong> action is in development and is expected in
a future release.</p>
<p>Because FauxAPI is a utility that interfaces with the pfSense <code>config.xml</code> there
are some cases where reloading the configuration file is not enough and you
may need to "tickle" pfSense a little more to do what you want.  This is not
common however a good example is getting newly defined network interfaces or
VLANs to be recognized.  These situations are easily handled by calling the
<strong>send_event</strong> action with the payload <strong>interface reload all</strong> - see the example
included below and refer to a the resolution to <a href="https://github.com/ndejong/pfsense_fauxapi/issues/10">user issue #10</a></p>
<p><strong>NB:</strong> <em>As at FauxAPI v1.2 the <strong>function_call</strong> action has been introduced that
now provides the ability to issue function calls directly into pfSense.</em></p>
<hr>
<h3>
<a id="user-content-installation" class="anchor" href="#installation" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Installation</h3>
<p>Until the FauxAPI is added to the pfSense FreeBSD-ports tree you will need to
install manually from <strong>root</strong> as shown:-</p>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">set</span> fauxapi_baseurl=<span class="pl-s"><span class="pl-pds">'</span>https://raw.githubusercontent.com/ndejong/pfsense_fauxapi/master/package<span class="pl-pds">'</span></span>
<span class="pl-c1">set</span> fauxapi_latest=<span class="pl-s"><span class="pl-pds">`</span>curl --silent <span class="pl-smi">$fauxapi_baseurl</span>/LATEST<span class="pl-pds">`</span></span>
fetch <span class="pl-smi">$fauxapi_baseurl</span>/<span class="pl-smi">$fauxapi_latest</span>
pkg install <span class="pl-smi">$fauxapi_latest</span></pre></div>
<p>Installation and de-installation is quite straight forward, further examples can
be found <a href="https://github.com/ndejong/pfsense_fauxapi/tree/master/package">here</a>.</p>
<p>Refer to the published package <a href="https://github.com/ndejong/pfsense_fauxapi/blob/master/package/SHA256SUMS"><code>SHA256SUMS</code></a></p>
<hr>
<h3>
<a id="user-content-debugging" class="anchor" href="#debugging" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Debugging</h3>
<p>FauxAPI comes with awesome debug logging capability, simply insert <code>__debug=true</code>
as a URL request parameter and the response data will contain rich debugging log
data about the flow of the request.</p>
<p>If you are looking for more debugging at various points feel free to submit a
pull request or lodge an issue describing your requirement and I'll see what
can be done to accommodate.</p>
<hr>
<p><a name="user-content-client_libraries"></a></p>
<h3>
<a id="user-content-client-libraries" class="anchor" href="#client-libraries" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Client libraries</h3>
<h4>
<a id="user-content-python" class="anchor" href="#python" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Python</h4>
<p>A <a href="https://github.com/ndejong/pfsense_fauxapi/tree/master/client-libs">Python interface</a>
to pfSense was perhaps the most desired end-goal at the onset of the FauxAPI
package project.  Anyone that has tried to parse the pfSense <code>config.xml</code> files
using a Python based library will understand that things don't quite work out as
expected or desired.</p>
<div class="highlight highlight-source-python"><pre><span class="pl-k">import</span> pprint, sys
<span class="pl-k">from</span> fauxapi_lib <span class="pl-k">import</span> FauxapiLib
FauxapiLib <span class="pl-k">=</span> FauxapiLib(<span class="pl-s"><span class="pl-pds">'</span>&lt;host-address&gt;<span class="pl-pds">'</span></span>, <span class="pl-s"><span class="pl-pds">'</span>&lt;fauxapi-key&gt;<span class="pl-pds">'</span></span>, <span class="pl-s"><span class="pl-pds">'</span>&lt;fauxapi-secret&gt;<span class="pl-pds">'</span></span>)

aliases <span class="pl-k">=</span> FauxapiLib.config_get(<span class="pl-s"><span class="pl-pds">'</span>aliases<span class="pl-pds">'</span></span>)
<span class="pl-c"><span class="pl-c">#</span># perform some kind of manipulation to `aliases` here ##</span>
pprint.pprint(FauxapiLib.config_set(aliases, <span class="pl-s"><span class="pl-pds">'</span>aliases<span class="pl-pds">'</span></span>))</pre></div>
<p>It is recommended to review <a href="https://github.com/ndejong/pfsense_fauxapi/blob/master/client-libs/python-lib-test-example.py"><code>python-lib-test-example.py</code></a>
to observe worked examples with the library.  Of small note is that the Python
library supports the ability to get and set single sections of the pfSense
system, not just the entire system configuration as with the Bash library.</p>
<h4>
<a id="user-content-bash" class="anchor" href="#bash" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Bash</h4>
<p>The <a href="https://github.com/ndejong/pfsense_fauxapi/tree/master/client-libs">Bash client library</a>
makes it possible to add a line with <code>source fauxapi_lib.sh</code> to your bash script
and then access a pfSense host configuration directly as a JSON string</p>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">source</span> fauxapi_lib.sh
<span class="pl-k">export</span> fauxapi_auth=<span class="pl-s"><span class="pl-pds">`</span>fauxapi_auth <span class="pl-k">&lt;</span>fauxapi-key<span class="pl-k">&gt;</span> <span class="pl-k">&lt;</span>fauxapi-secret<span class="pl-k">&gt;</span><span class="pl-pds">`</span></span>

fauxapi_config_get <span class="pl-k">&lt;</span>host-address<span class="pl-k">&gt;</span> <span class="pl-k">|</span> jq .data.config <span class="pl-k">&gt;</span> /tmp/config.json
<span class="pl-c"><span class="pl-c">#</span># perform some kind of manipulation to `/tmp/config.json` here ##</span>
fauxapi_config_set <span class="pl-k">&lt;</span>host-address<span class="pl-k">&gt;</span> /tmp/config.json</pre></div>
<p>It is recommended to review <a href="https://github.com/ndejong/pfsense_fauxapi/blob/master/client-libs/bash-lib-test-example.sh"><code>bash-lib-test-example.sh</code></a>
to get a better idea how to use it.</p>
<h4>
<a id="user-content-php" class="anchor" href="#php" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>PHP</h4>
<p>A PHP interface does not yet exist, it should be fairly easy to develop by
observing the Bash and Python examples - if you do please submit it as a github
pull request, there are no doubt others that will appreciate a PHP interface.</p>
<hr>
<h3>
<a id="user-content-api-authentication" class="anchor" href="#api-authentication" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Authentication</h3>
<p>A deliberate design decision to decouple FauxAPI authentication from both the
pfSense user authentication and the pfSense <code>config.xml</code> system.  This was done
to limit the possibility of an accidental API change that removes access to the
host.  It also seems more prudent to only establish API user(s) manually via the
FauxAPI <code>/etc/fauxapi/credentials.ini</code> file - happy to receive feedback about
this approach.</p>
<p>The two sample FauxAPI keys (PFFAexample01 and PFFAexample02) and their
associated secrets in the sample <code>credentials.ini</code> file are hard-coded to be
inoperative, you must create entirely new values before your client scripts
will be able to issue commands to FauxAPI.</p>
<p>API authentication itself is performed on a per-call basis with the auth value
inserted as an additional <strong>fauxapi-auth</strong> HTTP request header, it can be
calculated as such:-</p>
<pre><code>fauxapi-auth: &lt;apikey&gt;:&lt;timestamp&gt;:&lt;nonce&gt;:&lt;hash&gt;

For example:-
fauxapi-auth: PFFA4797d073:20161119Z144328:833a45d8:9c4f96ab042f5140386178618be1ae40adc68dd9fd6b158fb82c99f3aaa2bb55
</code></pre>
<p>Where the &lt;hash&gt; value is calculated like so:-</p>
<pre><code>&lt;hash&gt; = sha256(&lt;apisecret&gt;&lt;timestamp&gt;&lt;nonce&gt;)
</code></pre>
<p>This is all handled in the <a href="#user-content-client_libraries">client libraries</a>
provided, but as can be seen it is relatively easy to implement even in a Bash
shell script.</p>
<p>Getting the API credentials right seems to be a common source of confusion in
getting started with FauxAPI because the rules about valid API keys and secret
values are pedantic to help make ensure poor choices are not made.</p>
<p>The API key + API secret values that you will need to create in <code>/etc/fauxapi/credentials.ini</code>
have the following rules:-</p>
<ul>
<li>&lt;apikey_value&gt; and &lt;apisecret_value&gt; may have alphanumeric chars ONLY!</li>
<li>&lt;apikey_value&gt; MUST start with the prefix PFFA (pfSense Faux API)</li>
<li>&lt;apikey_value&gt; MUST be &gt;= 12 chars AND &lt;= 40 chars in total length</li>
<li>&lt;apisecret_value&gt; MUST be &gt;= 40 chars AND &lt;= 128 chars in length</li>
<li>you must not use the sample key/secret in the <code>credentials.ini</code> since they
are hard coded to fail.</li>
</ul>
<p>To make things easier consider using the following shell commands to generate
valid values:-</p>
<h4>
<a id="user-content-apikey_value" class="anchor" href="#apikey_value" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>apikey_value</h4>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">echo</span> PFFA<span class="pl-s"><span class="pl-pds">`</span>head /dev/urandom <span class="pl-k">|</span> base64 -w0 <span class="pl-k">|</span> tr -d /+= <span class="pl-k">|</span> head -c 20<span class="pl-pds">`</span></span></pre></div>
<h4>
<a id="user-content-apisecret_value" class="anchor" href="#apisecret_value" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>apisecret_value</h4>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">echo</span> <span class="pl-s"><span class="pl-pds">`</span>head /dev/urandom <span class="pl-k">|</span> base64 -w0 <span class="pl-k">|</span> tr -d /+= <span class="pl-k">|</span> head -c 60<span class="pl-pds">`</span></span></pre></div>
<p>NB: Make sure the client side clock is within 60 seconds of the pfSense host
clock else the auth token values calculated by the client will not be valid - 60
seconds seems tight, however, provided you are using NTP to look after your
system time it's quite unlikely to cause issues - happy to receive feedback
about this.</p>
<hr>
<h3>
<a id="user-content-api-authorization" class="anchor" href="#api-authorization" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Authorization</h3>
<p>The file <code>/etc/fauxapi/credentials.ini</code> additionally provides a method to restrict
the API actions available to the API key using the <strong>permit</strong> configuration
parameter.  Permits are comma delimited and may contain * wildcards to match more
than one rule as shown in the example below.</p>
<pre><code>[PFFAexample01]
secret = abcdefghijklmnopqrstuvwxyz0123456789abcd
permit = alias_*, config_*, gateway_*, rule_*, send_*, system_*, function_*
owner = example key PFFAexample01 - hardcoded to be inoperative
</code></pre>
<hr>
<h3>
<a id="user-content-api-rest-actions" class="anchor" href="#api-rest-actions" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API REST Actions</h3>
<p>The following REST based API actions are provided, example cURL call request
examples are provided for each.  The API user is perhaps more likely to interface
with the <a href="#user-content-client_libraries">client libraries</a> as documented above
rather than directly with these REST end-points.</p>
<p>The framework around the FauxAPI has been put together with the idea of being
able to easily add more actions at a later time, if you have ideas for actions
that might be useful be sure to get in contact.</p>
<p>NB: the cURL requests below use the '--insecure' switch because many pfSense
deployments do not deploy certificate chain signed SSL certificates.  A reasonable
improvement in this regard might be to implement certificate pinning at the
client side to hence remove scope for man-in-middle concerns.</p>
<p><br><a name="user-content-alias_update_urltables"></a></p>
<h4>
<a id="user-content-alias_update_urltables" class="anchor" href="#alias_update_urltables" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>alias_update_urltables</h4>
<ul>
<li>Causes the pfSense host to immediately update any urltable alias entries
from their (remote) source URLs.  Optionally update just one table by
specifying the table name, else all tables are updated.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>table</strong> (optional, default = null)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=alias_update_urltables<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>598ec756b4d09<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>alias_update_urltables<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>updates<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
      <span class="pl-s"><span class="pl-pds">"</span>bruteforceblocker<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
        <span class="pl-s"><span class="pl-pds">"</span>url<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/bruteforceblocker.ipset<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>status<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
          <span class="pl-s"><span class="pl-pds">"</span>no changes.<span class="pl-pds">"</span></span>
        ]
      }
    }
  }
}</pre></div>
<p><br><a name="user-content-config_backup"></a></p>
<h4>
<a id="user-content-config_backup" class="anchor" href="#config_backup" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_backup</h4>
<ul>
<li>Causes the system to take a configuration backup and add it to the regular
set of pfSense system backups at <code>/cf/conf/backup/</code>
</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_backup<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583012fea254f<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_backup<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>backup_config_file<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>/cf/conf/backup/config-1479545598.xml<span class="pl-pds">"</span></span>
  }
}</pre></div>
<p><br><a name="user-content-config_backup_list"></a></p>
<h4>
<a id="user-content-config_backup_list" class="anchor" href="#config_backup_list" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_backup_list</h4>
<ul>
<li>Returns a list of the currently available pfSense system configuration backups.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_backup_list<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583065cb670db<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_backup_list<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>backup_files<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
      {
        <span class="pl-s"><span class="pl-pds">"</span>filename<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>/cf/conf/backup/config-1479545598.xml<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>timestamp<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>20161119Z144635<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>description<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>fauxapi-PFFA4797d073@192.168.10.10: update via fauxapi for callid: 583012fea254f<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>version<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>15.5<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>filesize<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-c1">18535</span>
      },
      <span class="pl-k">...</span>.</pre></div>
<p><br><a name="user-content-config_get"></a></p>
<h4>
<a id="user-content-config_get" class="anchor" href="#config_get" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_get</h4>
<ul>
<li>Returns the system configuration as a JSON formatted string.  Additionally,
using the optional <strong>config_file</strong> parameter it is possible to retrieve backup
configurations by providing the full path to it under the <code>/cf/conf/backup</code>
path.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>config_file</strong> (optional, default=<code>/cf/config/config.xml</code>)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_get<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
    <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583012fe39f79<span class="pl-pds">"</span></span>,
    <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_get<span class="pl-pds">"</span></span>,
    <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
    <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
      <span class="pl-s"><span class="pl-pds">"</span>config_file<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>/cf/conf/config.xml<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>config<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
        <span class="pl-s"><span class="pl-pds">"</span>version<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>15.5<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>staticroutes<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>snmpd<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
          <span class="pl-s"><span class="pl-pds">"</span>syscontact<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
          <span class="pl-s"><span class="pl-pds">"</span>rocommunity<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>public<span class="pl-pds">"</span></span>,
          <span class="pl-s"><span class="pl-pds">"</span>syslocation<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>
        },
        <span class="pl-s"><span class="pl-pds">"</span>shaper<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>installedpackages<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
          <span class="pl-s"><span class="pl-pds">"</span>pfblockerngsouthamerica<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
            <span class="pl-s"><span class="pl-pds">"</span>config<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
             <span class="pl-k">...</span>.</pre></div>
<p>Hint: use <code>jq</code> to parse the response JSON and obtain the config only, as such:-</p>
<div class="highlight highlight-source-shell"><pre>cat /tmp/faux-config-get-output-from-curl.json <span class="pl-k">|</span> jq .data.config <span class="pl-k">&gt;</span> /tmp/config.json</pre></div>
<p><br><a name="user-content-config_reload"></a></p>
<h4>
<a id="user-content-config_reload" class="anchor" href="#config_reload" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_reload</h4>
<ul>
<li>Causes the pfSense system to perform a reload of the <code>config.xml</code> file, by
default this happens when the <strong>config_set</strong> action occurs hence there is
normally no need to explicitly call this after a <strong>config_set</strong> action.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_reload<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>5831226e18326<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_reload<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>
}</pre></div>
<p><br><a name="user-content-config_restore"></a></p>
<h4>
<a id="user-content-config_restore" class="anchor" href="#config_restore" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_restore</h4>
<ul>
<li>Restores the pfSense system to the named backup configuration.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>config_file</strong> (required, full path to the backup file to restore)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_restore&amp;config_file=/cf/conf/backup/config-1479545598.xml<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583126192a789<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_restore<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>config_file<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>/cf/conf/backup/config-1479545598.xml<span class="pl-pds">"</span></span>
  }
}</pre></div>
<p><br><a name="user-content-config_set"></a></p>
<h4>
<a id="user-content-config_set" class="anchor" href="#config_set" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_set</h4>
<ul>
<li>Sets a full system configuration and (by default) takes a system config
backup and (by default) causes the system config to be reloaded once
successfully written and tested.</li>
<li>NB: be sure to pass the <em>FULL</em> system configuration in here, not just the
piece you wish to adjust!</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params:
<ul>
<li>
<strong>do_backup</strong> (optional, default = true)</li>
<li>
<strong>do_reload</strong> (optional, default = true)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data @/tmp/config.json \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_set<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583065cae8993<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>config_set<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>do_backup<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-c1">true</span>,
    <span class="pl-s"><span class="pl-pds">"</span>do_reload<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-c1">true</span>
  }
}</pre></div>
<p><br><a name="user-content-function_call"></a></p>
<h4>
<a id="user-content-function_call" class="anchor" href="#function_call" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>function_call</h4>
<ul>
<li>Call directly a pfSense PHP function with API user supplied parameters.  Note
that is action is a <em>VERY</em> raw interface into the inner workings of pfSense
and it is not recommended for API users that do not have a solid understanding
of PHP and pfSense.  Additionally, not all pfSense functions are appropriate
to be called through the FauxAPI and only very limited testing has been
performed against the possible outcomes and responses.  It is possible to
harm your pfSense system if you do not 100% understand what is going on.</li>
<li>Functions to be called via this interface <em>MUST</em> be defined in the file
<code>/etc/inc/fauxapi/pfsense_function_calls.txt</code> only a handful very basic and
read-only pfSense functions are enabled by default.</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data <span class="pl-s"><span class="pl-pds">"</span>{<span class="pl-cce">\"</span>function<span class="pl-cce">\"</span>: <span class="pl-cce">\"</span>get_services<span class="pl-cce">\"</span>}<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=function_call<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>59a29e5017905<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>function_call<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>return<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
      {
        <span class="pl-s"><span class="pl-pds">"</span>name<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>unbound<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>description<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>DNS Resolver<span class="pl-pds">"</span></span>
      },
      {
        <span class="pl-s"><span class="pl-pds">"</span>name<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ntpd<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>description<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>NTP clock sync<span class="pl-pds">"</span></span>
      },
      <span class="pl-k">...</span>.
</pre></div>
<p><br><a name="user-content-gateway_status"></a></p>
<h4>
<a id="user-content-gateway_status" class="anchor" href="#gateway_status" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>gateway_status</h4>
<ul>
<li>Returns gateway status data.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=gateway_status<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>598ecf3e7011e<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>gateway_status<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>gateway_status<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
      <span class="pl-s"><span class="pl-pds">"</span>10.22.33.1<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
        <span class="pl-s"><span class="pl-pds">"</span>monitorip<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>8.8.8.8<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>srcip<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>10.22.33.100<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>name<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>GW_WAN<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>delay<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>4.415ms<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>stddev<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>3.239ms<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>loss<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>0.0%<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>status<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>none<span class="pl-pds">"</span></span>
      }
    }
  }
}</pre></div>
<p><br><a name="user-content-rule_get"></a></p>
<h4>
<a id="user-content-rule_get" class="anchor" href="#rule_get" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>rule_get</h4>
<ul>
<li>Returns the numbered list of loaded pf rules from a <code>pfctl -sr -vv</code> command
on the pfSense host.  An empty rule_number parameter causes all rules to be
returned.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>rule_number</strong> (optional, default = null)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=rule_get&amp;rule_number=5<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583c279b56958<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>rule_get<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>rules<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
      {
        <span class="pl-s"><span class="pl-pds">"</span>rule<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>anchor <span class="pl-cce">\"</span>openvpn/*<span class="pl-cce">\"</span> all<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>evaluations<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>14134<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>packets<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>0<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>bytes<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>0<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>states<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>0<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>inserted<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>21188<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>statecreations<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>0<span class="pl-pds">"</span></span>
      }
    ]
  }
}</pre></div>
<p><br><a name="user-content-send_event"></a></p>
<h4>
<a id="user-content-send_event" class="anchor" href="#send_event" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>send_event</h4>
<ul>
<li>Performs a pfSense "send_event" command to cause various pfSense system
actions as is also available through the pfSense console interface.  The
following standard pfSense send_event combinations are permitted:-
<ul>
<li>filter: reload, sync</li>
<li>interface: all, newip, reconfigure</li>
<li>service: reload, restart, sync</li>
</ul>
</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data <span class="pl-s"><span class="pl-pds">"</span>[<span class="pl-cce">\"</span>interface reload all<span class="pl-cce">\"</span>]<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=send_event<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>58312bb3398bc<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>send_event<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>
}</pre></div>
<p><br><a name="user-content-system_reboot"></a></p>
<h4>
<a id="user-content-system_reboot" class="anchor" href="#system_reboot" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>system_reboot</h4>
<ul>
<li>Just as it says, reboots the system.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=system_reboot<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>58312bb3487ac<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>system_reboot<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>
}</pre></div>
<p><br><a name="user-content-system_stats"></a></p>
<h4>
<a id="user-content-system_stats" class="anchor" href="#system_stats" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>system_stats</h4>
<ul>
<li>Returns various useful system stats.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=system_stats<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre>{
  <span class="pl-s"><span class="pl-pds">"</span>callid<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>583d5ce3301f4<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>action<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>system_stats<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>message<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>ok<span class="pl-pds">"</span></span>,
  <span class="pl-s"><span class="pl-pds">"</span>data<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
    <span class="pl-s"><span class="pl-pds">"</span>stats<span class="pl-pds">"</span></span><span class="pl-k">:</span> {
      <span class="pl-s"><span class="pl-pds">"</span>cpu<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>2<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>mem<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>16<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>uptime<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>9 Days 20 Hours 02 Minutes 08 Seconds<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>states<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>364/48000<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>temp<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>datetime<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>20161129Z104804<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>interfacestatistics<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>interfacestatus<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>cpufreq<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span><span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>load_average<span class="pl-pds">"</span></span><span class="pl-k">:</span> [
        <span class="pl-s"><span class="pl-pds">"</span>0.29<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>0.29<span class="pl-pds">"</span></span>,
        <span class="pl-s"><span class="pl-pds">"</span>0.28<span class="pl-pds">"</span></span>
      ],
      <span class="pl-s"><span class="pl-pds">"</span>mbuf<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>1016/30414<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>mbufpercent<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>3<span class="pl-pds">"</span></span>,
      <span class="pl-s"><span class="pl-pds">"</span>statepercent<span class="pl-pds">"</span></span><span class="pl-k">:</span> <span class="pl-s"><span class="pl-pds">"</span>1<span class="pl-pds">"</span></span>
    }
  }
}</pre></div>
<hr>
<h3>
<a id="user-content-versions-and-testing" class="anchor" href="#versions-and-testing" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Versions and Testing</h3>
<p>The FauxAPI has been developed against pfSense 2.3.2, 2.3.3 and 2.3.4 it has
not (yet) been tested against 2.3.0 or 2.3.1 or the (currently) in development
2.4 releases.  Further, it is apparent that the pfSense packaging technique
changed significantly prior to 2.3.x so it is unlikely that it will be
backported to anything prior to 2.3.0.</p>
<p>Testing is reasonable but does not achieve 100% code coverage within the FauxAPI
codebase.  Two client side test scripts (1x Bash, 1x Python) that both
demonstrate and test all possible server side actions are provided.  Under the
hood FauxAPI, performs real-time sanity checks and tests to make sure the user
supplied configurations will save, load and reload as expected.</p>
<p><strong>Shout Out:</strong> <em>Anyone that happens to know of <em>any</em> test harness or test code
for pfSense please get in touch - I'd very much prefer to integrate with existing
pfSense test infrastructure if it already exists.</em></p>
<hr>
<h3>
<a id="user-content-releases" class="anchor" href="#releases" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Releases</h3>
<h4>
<a id="user-content-v10---2016-11-20" class="anchor" href="#v10---2016-11-20" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.0 - 2016-11-20</h4>
<ul>
<li>initial release</li>
</ul>
<h4>
<a id="user-content-v11---2017-08-12" class="anchor" href="#v11---2017-08-12" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.1 - 2017-08-12</h4>
<ul>
<li>2x new API actions <strong>alias_update_urltables</strong> and <strong>gateway_status</strong>
</li>
<li>update documentation to address common points of confusion, especially the
requirement to provide the <em>full</em> config file not just the portion to be
updated.</li>
<li>testing against pfSense 2.3.2 and 2.3.3</li>
</ul>
<h4>
<a id="user-content-v12---2017-08-27" class="anchor" href="#v12---2017-08-27" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.2 - 2017-08-27</h4>
<ul>
<li>new API action <strong>function_call</strong> allowing the user to reach deep into the inner
code infrastructure of pfSense, this feature is intended for people with a
solid understanding of PHP and pfSense.</li>
<li>the <code>credentials.ini</code> file now provides a way to control the permitted API
actions.</li>
<li>various update documentation updates.</li>
<li>testing against pfSense 2.3.4</li>
</ul>
<hr>
<h3>
<a id="user-content-fauxapi-license" class="anchor" href="#fauxapi-license" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>FauxAPI License</h3>
<pre><code>Copyright 2017 Nicholas de Jong  

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
</code></pre>
<!--READMEEND-->
</div>

<?php 
    include('foot.inc');
?>