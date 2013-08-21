function disableStaticOptions(value) {
	if (value == 'dhcp') {
		document.NetworkSettings.ip.disabled = true;
		document.NetworkSettings.netmask.disabled = true;
		document.NetworkSettings.gateway.disabled = true;
		document.NetworkSettings.dns1.disabled = true;
		document.NetworkSettings.dns2.disabled = true;
	} else {
		document.NetworkSettings.ip.disabled = false;
		document.NetworkSettings.netmask.disabled = false;
		document.NetworkSettings.gateway.disabled = false;
		document.NetworkSettings.dns1.disabled = false;
		document.NetworkSettings.dns2.disabled = false;
	}
}