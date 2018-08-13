import struct
import collections
import dhcp


TYPE_LIST = 1
TYPE_SELECT = 2
TYPE_FAILED = 3

CODE_TYPE = 1
CODE_VERSION = 2
CODE_SERVER_ID = 3
CODE_SERVER_PRIORITY = 4
CODE_REPLY_PORT = 5
CODE_DEFAULT_BOOT_IMAGE = 7
CODE_SELECTED_BOOT_IMAGE = 8
CODE_BOOT_IMAGE_LIST = 9
CODE_MAX_MESSAGE_SIZE = 12
CODE_SHADOW_MOUNT_URL = 128
CODE_SHADOW_FILE_PATH = 129
CODE_MACHINE_NAME = 130

BSDP_TYPES = {
    CODE_TYPE: 'int8',
    CODE_VERSION: 'int16',
    CODE_SERVER_ID: 'ip',
    CODE_SERVER_PRIORITY: 'int16',
    CODE_REPLY_PORT: 'int16',
    CODE_DEFAULT_BOOT_IMAGE: 'int32',
    CODE_SELECTED_BOOT_IMAGE: 'int32',
    CODE_BOOT_IMAGE_LIST: '*oct',
    CODE_MAX_MESSAGE_SIZE: 'int16',
    CODE_SHADOW_MOUNT_URL: 'string',
    CODE_SHADOW_FILE_PATH: 'string',
    CODE_MACHINE_NAME: 'string',
}

class BsdpPacket:
    def __init__(self):
        self.options = { }

    #
    # Return the packet as a printable string.
    #
    def str(self):
        string = ''
        for opt in self.options:
            if opt in BSDP_TYPES:
                fmt = BSDP_TYPES[opt]
            else:
                fmt = '*oct'
            string += 'Option {:d}: {:s}\n'.format(int(opt), dhcp.DhcpPacket.format_for_display(fmt, self.options[opt]))

        return string

    #
    # Parse the data into a BsdpPacket.
    #
    def decode(self, data):
        if isinstance(data, collections.Sequence):
            data = struct.pack(str(len(data)) + 'B', *data)
        while len(data):
            vals = struct.unpack('=BB', data[0:2])
            code = vals[0]
            length = vals[1]
            if code in BSDP_TYPES:
                fmt = BSDP_TYPES[code]
            else:
                fmt = '*oct'
            self.options[code] = dhcp.DhcpPacket.decode_value(fmt, data[2:2+length])
            data = data[2+length:]

    #
    # Encode the packet into a data stream. If the unpack parameter is
    # True then the data is unpacked into an array of integers that
    # each represent a byte of the data, useful for then storing in a
    # DHCP packet.
    #
    def encode(self, unpack = False):
        data = ''
        for opt in self.options:
            if opt in BSDP_TYPES:
                fmt = BSDP_TYPES[opt]
            else:
                fmt = '*B'

            dat = dhcp.DhcpPacket.encode_value(fmt, self.options[opt])
            data += struct.pack('=BB', opt, len(dat))
            data += dat

        if unpack:
            return struct.unpack(str(len(data)) + 'B', data)
        else:
            return data

    #
    # Message type.
    #
    def setType(self, value):
        self.options[CODE_TYPE] = value

    def getType(self):
        if CODE_TYPE in self.options:
            return self.options[CODE_TYPE]

        return None

    #
    # Version.
    #
    def setVersion(self, value):
        self.options[CODE_VERSION] = value

    def getVersion(self):
        if CODE_VERSION in self.options:
            return self.options[CODE_VERSION]

        return None

    #
    # Server id.
    # IP Address of BSDP server.
    #
    def setServerID(self, value):
        self.options[CODE_SERVER_ID] = value

    def getServerID(self):
        if CODE_SERVER_ID in self.options:
            return self.options[CODE_SERVER_ID]

        return None

    #
    # Server priority.
    # Priority of server over others on the network.
    #
    def setServerPriority(self, value):
        self.options[CODE_SERVER_PRIORITY] = value

    def getServerPriority(self):
        if CODE_SERVER_PRIORITY in self.options:
            return self.options[CODE_SERVER_PRIORITY]

        return None

    #
    # Reply port.
    # Port the client is listening on.
    #
    def setReplyPort(self, value):
        self.options[CODE_REPLY_PORT] = value

    def getReplyPort(self):
        if CODE_REPLY_PORT in self.options:
            return self.options[CODE_REPLY_PORT]

        return None

    #
    # Default boot image ID.
    #
    def setDefaultBootImage(self, value):
        self.options[CODE_DEFAULT_BOOT_IMAGE] = value

    def getDefaultBootImage(self):
        if CODE_DEFAULT_BOOT_IMAGE in self.options:
            return self.options[CODE_DEFAULT_BOOT_IMAGE]

        return None

    #
    # Selected boot image ID.
    #
    def setSelectedBootImage(self, value):
        self.options[CODE_SELECTED_BOOT_IMAGE] = value

    def getSelectedBootImage(self):
        if CODE_SELECTED_BOOT_IMAGE in self.options:
            return self.options[CODE_SELECTED_BOOT_IMAGE]

        return None

    #
    # Maximum message size.
    #
    def setMaxMessageSize(self, value):
        self.options[CODE_MAX_MESSAGE_SIZE] = value

    def getMaxMessageSize(self):
        if CODE_MAX_MESSAGE_SIZE in self.options:
            return self.options[CODE_MAX_MESSAGE_SIZE]

        return None

    #
    # Shadow Mount URL
    # afp://[username:password@]server/SharePoint
    #
    def setShadowMountURL(self, value):
        self.options[CODE_SHADOW_MOUNT_URL] = value

    def getShadowMountURL(self):
        if CODE_SHADOW_MOUNT_URL in self.options:
            return self.options[CODE_SHADOW_MOUNT_URL]

        return None

    #
    # Shadow File Path
    # Directory/Filename
    #
    def setShadowFilePath(self, value):
        self.options[CODE_SHADOW_FILE_PATH] = value

    def getShadowFilePath(self):
        if CODE_SHADOW_FILE_PATH in self.options:
            return self.options[CODE_SHADOW_FILE_PATH]

        return None

    #
    # Machine Name
    # Network name of the machine for sharing purposes.
    #
    def setMachineName(self, value):
        self.options[CODE_MACHINE_NAME] = value

    def getMachineName(self):
        if CODE_MACHINE_NAME in self.options:
            return self.options[CODE_MACHINE_NAME]

        return None

    #
    # Append a new image name to the list.
    #
    def appendBootImageList(self, ident, name):
        if CODE_BOOT_IMAGE_LIST in self.options:
            data = self.options[CODE_BOOT_IMAGE_LIST]
        else:
            data = [ ]

        if len(data) + 4 + 1 + len(name) > 255:
            return

        data += [ord(c) for c in struct.pack('!L', ident)]
        data += [ord(c) for c in struct.pack('!B', len(name))]
        data += [ord(c) for c in struct.pack(str(len(name)) + 's', name)]
        self.options[CODE_BOOT_IMAGE_LIST] = data

