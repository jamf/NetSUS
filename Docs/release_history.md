# Release History

<table>
    <thead>
        <tr>
            <th>Version</th>
            <th>Changes</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                v4.0.0
            </td>
            <td>
                <ul>
                    <li>Renamed to NetBoot/SUS/LP (NetSUSLP) for reference to LDAP Proxy.</li>
                    <li>Added El Capitan support for SUS.</li>
                    <li>Added firewall functionality with port managing for running NetSUSLP services by using app armor.</li>
                    <li>Added ability to disabled WebAdmin interface.</li>
                    <li>Added LDAP Proxy functionality with the use of slapd.</li>
                    <li>Added GAWK installation for WebAdmin on Ubuntu operating systems.</li>
                    <li>Added functionality to only enable services as needed.</li>
                    <li>Added functionality to update Ubuntu apt-get repository to prevent failures on service installation.</li>
                    <li>Added certificate page to allow tomcat or slapd certificates, and configured an installation to use a self-signed certificate.</li>
                    <li>Changed NetBoot page to enable SMB for uploading a NetBoot file, and then disable it when it is not in use.</li>
                    <li>OVA updated to use 2GB of memory and hard drive space increased to use 300 GB of hard drive space.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                v3.0
            </td>
            <td>
                <ul>
                    <li>The NetBoot/SUS/LP Server can now be installed on RHEL and CentOS.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                v2.0
            </td>
            <td>
                <ul>
                    <li>Added the option to install the NetBoot/SUS/LP server using an installer.</li>
                    <li>Updated the NetBoot/SUS/LP server web application GUI to match the JSS v9.0 and later.</li>
                    <li>The NetBoot server hosted by the NetBoot/SUS/LP server now uses HTTP instead of NFS.</li>
                    <li>Updated the version of Reposado that is used by the NetBoot/SUS/LP server.</li>
                </ul>
            </td>
        </tr>
    </tbody>
</table>