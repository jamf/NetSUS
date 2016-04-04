# Setting Up the NetBoot Server
To set up a NetBoot server, you need a NetBoot image (.nbi folder). For more information, see the following Knowledge Base article:[Creating a NetBoot Image and Setting Up a NetBoot Server](https://jamfnation.jamfsoftware.com/article.html?id=307)

1. Log in to the NetBoot/SUS/LP server web application.2. Click **NetBoot Server**.On a smartphone, this option is in the pop-up menu.3. Upload a NetBoot image:	* Click **Upload NetBoot Image**.

		<p align="left"><img src="images/attachments/netboot.png" width="500"></p>
			* You will be connected to the SMB share where NetBoot images are stored.	* Enter credentials for the SMB share and click **Connect**.	* Copy a NetBoot image folder (.nbi) to the SMB share. The nbi folder must follow the structure of this example. The names of the .nbi folder, the .dmg file and the .plist file may be different than what is shown.
	
		<p><img src="images/attachments/nbi_folder_structure.png" width="250"></p>

	**Important:** The name of the folder cannot contain any spaces.4. Return to the NetBoot/SUS/LP server web application and refresh the page.5. Choose the NetBoot image from the pop-up menu.

6. **Optional:** Enter a name for your Netboot Server. The name cannot contain spaces. If left blank the name will default to the name of your .nbi folder uploaded previously. For common issues on this setting please see the troubleshooting section below. 7. Choose subnets for the NetBoot image by entering a subnet and a netmask. Then click **Add Subnet**. 

	**Important**: One of the subnets must include the IP address of the NetBoot server.8. Click **Enable NetBoot**. If NetBoot is successfully enabled, the NetBoot status icon turns green.

##Troubleshooting

The best place to gather information on why your NetBoot Server might not be working is the "dhcpd" service logs in your system's default log locaion. For example:

on Ubuntu Server 14.04 you would enter the command

`grep "dhcpd" /var/log/syslog`

This will output a list of logs related to the dhcpd service to your terminal window.

###Common Issues

* Your NetBoot image shows the name "Faux NetBoot"

	Solution: Ensure your .nbi folder contains a .plist file. This file determines various settings for that NetBoot image, including the name. It should be located in this directory `/srv/NetBoot/NetBootSP0/your_nbi_name.nbi`. This file should be generated when the .nbi folder is created.
	
* The name entered in the web interface is not changing the NetBoot name

	Solution: You may not have write permisson for the .plist file in your .nbi folder. After you upload your .nbi folder the .plist file will be located in this directory `/srv/NetBoot/NetBootSP0/your_nbi_name.nbi`. Locate the plist file on your server and enter `chmod +w your_file.plist`. Be sure to stop and start your NetBoot instance through the web interface once this change has been made.
	
## Using the NetBoot Server with the Casper Suite**Note**: The instructions in this section are for the Casper Suite v9.0 or later. However, if you are using the Casper Suite v8.x, these instructions can still be followed loosely.
Like standard NetBoot servers, you can add the NetBoot server hosted by the NetBoot/SUS/LP server to the JSS. This allows you to use a policy or Casper Remote to boot managed computers to a NetBoot image.
When adding the NetBoot server to the JSS, enter the IP address specified in the NetBoot/SUS/LP server web application and choose the “Use default image” option from the NetBoot Image pop-up menu.
For more information on adding a NetBoot server to the JSS, see the “NetBoot Servers” section in the Casper Suite Administrator’s Guide.
For more information on using a policy or Casper Remote to boot computers to a NetBoot image, see the “Booting Computers to NetBoot Images” section in the Casper Suite Administrator’s Guide.