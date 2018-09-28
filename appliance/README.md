# Creating the NetSUSLP Appliance

### This guide is for project administrators of the NetSUSLP application. 

The following instructions describe how to use VirtualBox and Packer (utilizing the files contained in the 'appliance' directory) to create an appliance (ova file) of the NetSUSLP application. 

The purpose of the appliance file is to give users an easy to setup virtual machine that already has the NetSUSLP application installed. With the appliance the user can simply import the ova file into their preferred virtualization software and have a running NetSUSLP. 

## Requirements

**All requirements are open source and free to use for the purposes of this guide.**

* VirtualBox 
* Packer

## Installation

There are several options for installing VirtualBox and Packer. You can either download the installers found below or use various command line options. On a Windows or Redhat host machine it is best to use the downloaded installers. 

* VirtualBox [Download Installer Here](https://www.virtualbox.org/wiki/Downloads)
* Packer [Download Installer Here](https://www.packer.io/downloads.html)

On a OSX host machine using homebrew

	$ brew cask install virtualbox
	$ brew install packer
	
On a Debian host machine (Ubuntu). For packer you will need to use the downloaded installer

	$ sudo apt-get install virtualbox
		
	
## 1. Create the Base VM with Packer

1. Make sure any changes made to the NetSUSLP codebase are finalized and tested. If you do not have the current repository on your host machine, you will need to clone it. 

		$ git clone https://github.com/jamf/NetSUS.git
		
2. You will now create the installer for NetSUSLP. Running the CreateNetSUSInstaller.sh script will create a NetSUSLPInstaller.run file. In the base directory of your NetSUS project run the command:

		$ sudo ./CreateNetSUSInstaller.sh
		
3. You will now utilize packer to create a base VM with the necessary virtual hardware specifications. The created VM will not have the correct network settings. This process will may take some time if it is downloading an iso image and installing it onto a VM. If you already have the appropriate installation media, you may place it in the `NetSUS/appliance/iso/` directory, to avoid downloading during the build process. Ensure the filename and checksums match for the media in the setup-<base os>.json file before starting. You can observe the installation by starting VirtualBox and opening the 'NetSUSLP' VM. Packer will take care of the input necessary to install the operating system and NetSUSLP.\
   To proceed run this command in the `NetSUS/appliance/` directory:

		$ packer build setup-<base os>.json
	
   Where <base-os> is the appropriate operating system, e.g. `setup-ubuntu-16.04.json`
   
## 2. Configure and Convert the OVF to OVA

1. Once the packer build is finished the created ovf file will be located in `NetSUS/appliance/output-virtualbox-iso/`

2. Open VirtualBox and import the created ovf file located in the directory above. To import an ovf file select `File -> Import Appliance -> select file` in the VirtualBox manager page.

3. Do not spin up the imported VM you will want to add some network settings that will allow the VM to gain access to the internet. You can access the settings by right clicking on the NetSUSLP VM and selecting 'Settings', then enter the 'Network' tab. The settings that will depend on your host machine's hardware and network environment are 'Attach' and 'Name'. The 'Advanced' section must be set as depicted here:
	
 	<img src="../docs/images/attachments/vbox_network.png" width="600">
	
4. In VirtualBox manager, select `File -> Export Appliance -> NetSUSLP -> Continue`

5. Now select a location to put the exported ova file. Be sure to name the the file `NetSUSLP_<release_version> and select the OVF 1.0 format.

	<img src="../docs/images/attachments/vbox_export.png" width="750">
	
## 3. Test the Exported VM
	
1. Import the OVA file you just created as described in Section 2.2	

2. Spin up the imported VM, and ensure the system gets network connectivity and the web interface is functional.

3. Remove the imported VM from Virtual Box, the `NetSUS/appliance/output-virtualbox-iso/` directory, and the installation media from the `NetSUS/appliance/iso/` directory.
	
