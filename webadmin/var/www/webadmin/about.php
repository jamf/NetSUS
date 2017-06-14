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

	<h2>About</h2>

	<hr>

	<h3>Open Source Acknowledgments</h3>
	<div class="about">

		<p class="bold">Reposado</p>
		<p><a href="https://github.com/wdas/reposado/" target="_blank">https://github.com/wdas/reposado/</a></p>


		<p class="bold">ISC's DHCP</p>
		<p><a href="http://www.isc.org/software/dhcp/" target="_blank">http://www.isc.org/software/dhcp/</a></p>

		<p class="bold"><?php echo $os_name; ?></p>
		<?php if ($home_url != '') { ?>
			<p><a href="<?php echo $home_url; ?>" target="_blank"><?php echo $home_url; ?></a></p>
		<?php } ?>

		<?php if ($install_type == 'apt-get') { ?>
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
				<li>netatalk</li>
				<!-- <li>nfs-kernel-server</li> -->
				<li>openbsd-inetd</li>
				<li>parted</li>
				<?php if (version_compare(phpversion(), "7.0") < 0) {
					echo "<li>php5-ldap</li>";
				} else {
					echo "<li>php-ldap</li>";
					echo "<li>php-xml</li>";
				} ?>
				<li>policycoreutils</li>
				<li>python-configparser</li>
				<li>python-m2crypto</li>
				<li>python-pycurl</li>
				<li>samba</li>
				<li>slapd</li>
				<li>tftpd-hpa</li>
				<li>ufw</li>
				<li>whois</li>
			</ul>

		<?php } ?>
		<?php if ($install_type == 'yum') { ?>
			<p>The following installations, including any dependencies:</p>
			<ul>
				<li>avahi</li>
				<li>dialog</li>
				<li>dmidecode</li>
				<li>expect</li>
				<li>m2crypto</li>
				<li>mod_ssl</li>
				<li>netatalk</li>
				<!-- <li>nfs-utils</li> -->
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


	<h3 align="left">Support</h3>

	<div class="about">
		<p>
			The NetBoot/SUS/LDAP Proxy server is distributed "as is" by JAMF Software, LLC.  For support, please use the following resource:<br/><br/>
			<a href="https://www.jamf.com/jamf-nation/" target="_blank">https://www.jamf.com/jamf-nation/</a><br/><br/>
		</p>

	</div>


	<h3 align="left">Additional License Information</h3>

	<div class="about">
		<p>
			Copyright (C) 2017, JAMF Software, LLC<br/>
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
			<li>Neither the name of the JAMF Software, LLC nor the names of its contributors
				may be used to endorse or promote products derived from this software without
				specific prior written permission.</li>
		</ul>
		<br>

		<p>
			THIS SOFTWARE IS PROVIDED BY JAMF SOFTWARE, LLC "AS IS" AND ANY EXPRESS OR IMPLIED
			WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
			AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMF SOFTWARE, LLC
			BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
			DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
			LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
			THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
			OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
			POSSIBILITY OF SUCH DAMAGE.
		</p>
	</div>

<?php include "inc/footer.php"; ?>