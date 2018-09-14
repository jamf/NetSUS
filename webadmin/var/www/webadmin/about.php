<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "About";

include "inc/header.php";

$os_name = trim(suExec("getName"));
$home_url = trim(suExec("getHomeUrl"));
$install_type = trim(suExec("getInstallType"));
?>
			<style>
				.about {
					margin-left: 10px;
				}
				.about p.bold {
					font-weight: bold;
				}
				.about ul {
					list-style-type: disc;
					margin-left: 20px;
				}
			</style>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Information</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>About</h2>
				</div>
			</nav>

				<div style="padding: 70px 20px 16px; background-color: #f9f9f9;">
					<h3>Open Source Acknowledgments</h3>
					<div class="about">

						<p class="bold">Ace</p>
						<p><a href="https://ace.c9.io/" target="_blank">https://ace.c9.io/</a></p>

						<p class="bold">Bootstrap</p>
						<p><a href="https://getbootstrap.com/" target="_blank">https://getbootstrap.com/</a></p>

						<p class="bold">Bootstrap DateTimePicker</p>
						<p><a href="https://eonasdan.github.io/bootstrap-datetimepicker/" target="_blank">https://eonasdan.github.io/bootstrap-datetimepicker/</a></p>

						<p class="bold">Bootstrap Toggle</p>
						<p><a href="http://www.bootstraptoggle.com/" target="_blank">http://www.bootstraptoggle.com/</a></p>

						<p class="bold">DataTables</p>
						<p><a href="https://datatables.net/" target="_blank">https://datatables.net/</a></p>

						<p class="bold">jQuery</p>
						<p><a href="https://jquery.com/" target="_blank">https://jquery.com/</a></p>

						<p class="bold">Moment.js</p>
						<p><a href="https://momentjs.com/" target="_blank">https://momentjs.com/</a></p>

						<p class="bold">pybsdp</p>
						<p><a href="https://github.com/cabal95/pybsdp/" target="_blank">https://github.com/cabal95/pybsdp/</a></p>

						<p class="bold">Reposado</p>
						<p><a href="https://github.com/wdas/reposado/" target="_blank">https://github.com/wdas/reposado/</a></p>

						<p class="bold">timezonepicker</p>
						<p><a href="https://github.com/quicksketch/timezonepicker/" target="_blank">https://github.com/quicksketch/timezonepicker/</a></p>

						<p class="bold"><?php echo $os_name; ?></p>
<?php if ($home_url != '') { ?>
						<p><a href="<?php echo $home_url; ?>" target="_blank"><?php echo $home_url; ?></a></p>
<?php }
if ($install_type == 'apt-get') { ?>
						<p>The following apt-get repository installations, including any dependencies:</p>
						<ul>
							<li>apache2-utils</li>
							<li>curl</li>
							<li>dialog</li>
							<li>gawk</li>
							<?php
							if (version_compare(phpversion(), "7.0") < 0) {
								echo "<li>libapache2-mod-php5</li>";
							} else {
								echo "<li>libapache2-mod-php</li>"; }
							?>
							<li>lvm2</li>
							<li>netatalk</li>
							<li>nfs-kernel-server</li>
							<li>ntp</li>
							<li>openssh-server</li>
							<li>parted</li>
							<?php if (version_compare(phpversion(), "7.0") < 0) {
								echo "<li>php5-ldap</li>";
							} else {
								echo "<li>php-ldap</li>";
								echo "<li>php-xml</li>";
								echo "<li>php-zip</li>";
							} ?>
							<li>policycoreutils</li>
							<li>python-configparser</li>
							<li>python-m2crypto</li>
							<li>python-pycurl</li>
							<li>samba</li>
							<li>slapd</li>
							<li>software-properties-common</li>
							<li>tftpd-hpa</li>
							<li>ufw</li>
							<li>whois</li>
						</ul>
<?php }
if ($install_type == 'yum') { ?>
						<p>The following installations, including any dependencies:</p>
						<ul>
							<li>avahi</li>
							<li>dialog</li>
							<li>dmidecode</li>
							<li>expect</li>
							<li>lsof</li>
							<li>lvm2</li>
							<li>m2crypto</li>
							<li>mod_ssl</li>
							<li>netatalk</li>
							<li>nfs-utils</li>
							<li>ntpdate</li>
							<li>openldap-servers</li>
							<li>parted</li>
							<li>php</li>
							<li>php-ldap</li>
							<li>php-xml</li>
							<li>psmisc</li>
							<li>python-pycurl</li>
							<li>samba</li>
							<li>samba-client</li>
							<li>tftp-server</li>
							<li>vim-common</li>
						</ul>
<?php } ?>
				</div>
			</div>

			<hr>

			<div style="padding: 6px 20px 1px;">
				<h3 align="left">Support</h3>

				<div class="about">
					<p>The NetBoot/SUS/LDAP Proxy server is distributed "as is" by the Jamf Open Source Community. For support, please use the following resource:<br/><br/>
						<a href="https://www.jamf.com/jamf-nation/" target="_blank">https://www.jamf.com/jamf-nation/</a><br/><br/>
					</p>
				</div>
			</div>

			<hr>

			<div style="padding: 6px 20px 1px; background-color: #f9f9f9;">
				<div class="about">
					<p>
						Copyright (C) 2018, Jamf Open Source Community<br/>
						All rights reserved.<br/><br/>

						Redistribution and use in source and binary forms, with or without modification,
						are permitted provided that the following conditions are met:
					</p>
					<br>

					<ul>
						<li>Redistributions of source code must retain the above copyright notice, this
							list of conditions and the following disclaimer.</li>
						<br>
						<li>Redistributions in binary form must reproduce the above copyright notice,
							this list of conditions and the following disclaimer in the documentation
							and/or other materials provided with the distribution.</li>
						<br>
						<li>Neither the name of the Jamf nor the names of its contributors may be used to endorse
							or promote products derived from this software without specific prior written
							permission.</li>
					</ul>
					<br>

					<p>
						THIS SOFTWARE IS PROVIDED BY THE JAMF OPEN SOURCE COMMUNITY "AS IS" AND ANY EXPRESS OR
						IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
						MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
						THE JAMF OPEN SOURCE COMMUNITY BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
						EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
						SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
						HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
						TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
						EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
					</p>
				</div>
			</div>

			<hr>
<?php include "inc/footer.php"; ?>