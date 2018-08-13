import struct
import collections


#
# How data is stored in the options lists:
#
# oct    - Array of integers (0-255) specifying the byte values
# int8   - Single integer (0-255)
# int16  - Single integer (0-65535)
# int32  - Single integer (0-4294967295)
# ip     - String ('192.168.0.1')
# *XXX   - Array of type XXX from above('*ip': ['192.168.0.1', '192.168.4.23'])
# string - String ('Hello world')
#
# Options whose data type is unknown are stored as type of '*oct'.
#

MESSAGE_DISCOVER = 1
MESSAGE_OFFER = 2
MESSAGE_REQUEST = 3
MESSAGE_DECLINE = 4
MESSAGE_ACK = 5
MESSAGE_NAK = 6
MESSAGE_RELEASE = 7
MESSAGE_INFORM = 8

OPTION_ROOT_PATH = 17
OPTION_VENDOR_INFORMATION = 43
OPTION_MESSAGE_TYPE = 53
OPTION_SERVER_IDENTIFIER = 54
OPTION_MAXIMUM_MESSAGE_SIZE = 57
OPTION_VENDOR_CLASS = 60
OPTION_TFTP_SERVER_NAME = 66
OPTION_BOOTFILE_NAME = 67

DHCP_TYPES = {
    OPTION_ROOT_PATH: 'string',
    OPTION_VENDOR_INFORMATION: '*oct',
    OPTION_MESSAGE_TYPE: 'int8',
    OPTION_SERVER_IDENTIFIER: 'ip',
    OPTION_MAXIMUM_MESSAGE_SIZE: 'int16',
    OPTION_VENDOR_CLASS: 'string',
    OPTION_TFTP_SERVER_NAME: 'string',
    OPTION_BOOTFILE_NAME: 'string',
}

class DhcpPacket:
    #
    # Format the given value for display purposes, value should be
    # stored in "normal" use, not network byte order.
    #
    @staticmethod
    def format_for_display(fmt, value):
        if fmt == 'int8' or fmt == 'int16' or fmt == 'int32':
            return str(value)
        elif fmt == 'ip' or fmt == 'string':
            return str(value)
        elif fmt[0:1] == '*':
            if fmt[-3:] == 'oct':
                sep = ':'
            else:
                sep = ', '
            vals = [ ]
            if isinstance(value, collections.Sequence):
                for val in value:
                    vals.append(DhcpPacket.format_for_display(fmt[1:], val))
            else:
                vals.append(DhcpPacket.format_for_display(fmt[1:], value))
            return sep.join(vals)
        else: # Treat as octet
            return '{:02x}'.format(value)

    #
    # Decode a single value.
    #
    @staticmethod
    def decode_value(fmt, data):
        if fmt == 'int8':
            return struct.unpack('!B', data)[0]
        elif fmt == 'int16':
            return struct.unpack('!H', data)[0]
        elif fmt == 'int32':
            return struct.unpack('!L', data)[0]
        elif fmt == 'ip':
            return '.'.join(str(c) for c in struct.unpack('4B', data))
        elif fmt == 'string':
            return struct.unpack(str(len(data)) + 's', data)[0]
        elif fmt[0:1] == '*':
            newfmt = fmt[1:]
            if newfmt == 'int16':
                size = 2
            elif newfmt == 'int32' or newfmt == 'ip':
                size = 4
            else:
                size = 1
            vals = [ ]
            while len(data) > 0:
                vals.append(DhcpPacket.decode_value(newfmt, data[0:size]))
                data = data[size:]
            return vals
        else: # Treat as octet
            return ord(data)
    
    #
    # Encode a single value.
    #
    @staticmethod
    def encode_value(fmt, value):
        if fmt == 'int8':
            return struct.pack('!B', value)
        elif fmt == 'int16':
            return struct.pack('!H', value)
        elif fmt == 'int32':
            return struct.pack('!L', value)
        elif fmt == 'ip':
            return struct.pack('4B', *[int(c) for c in value.split('.')])
        elif fmt == 'string':
            return struct.pack(str(len(value)) + 's', value)
        elif fmt[0:1] == '*':
            newfmt = fmt[1:]
            if newfmt == 'int16':
                size = 2
            elif newfmt == 'int32' or newfmt == 'ip':
                size = 4
            else:
                size = 1
            data = ''
            if isinstance(value, collections.Sequence):
                for val in value:
                    data += DhcpPacket.encode_value(newfmt, val)
            else:
                data += DhcpPacket.encode_value(newfmt, value)
            return data
        else: # Treat as octet
            return chr(value)

    #
    # Encode a TLV tuple
    #
    @staticmethod
    def encode_tlv(code, type, value):
        data = DhcpPacket.encode_value(type, value)
        return struct.pack('BB', code, len(data)) + data

    #
    # Initialize new class instance.
    #
    def __init__(self):
        self.op = 0
        self.htype = 0
        self.hlen = 6
        self.hops = 0
        self.xid = [0, 0, 0, 0]
        self.secs = 0
        self.flags = 0
        self.ciaddr = '0.0.0.0'
        self.yiaddr = '0.0.0.0'
        self.siaddr = '0.0.0.0'
        self.giaddr = '0.0.0.0'
        self.chaddr = [0, 0, 0, 0, 0, 0]
        self.magic = [0x63, 0x82, 0x53, 0x63]
        self.options = { }


    #
    # Return the packet as a printable string.
    #
    def str(self):
        string = 'op: {:d}\n'.format(self.op)
        string += 'htype: {:d}\n'.format(self.htype)
        string += 'hlen: {:d}\n'.format(self.hlen)
        string += 'hops: {:d}\n'.format(self.hops)
        string += 'xid: {:s}\n'.format(DhcpPacket.format_for_display('*oct', self.xid))
        string += 'secs: {:d}\n'.format(self.secs)
        string += 'flags: 0x{:04x}\n'.format(self.flags)
        string += 'ciaddr: {:s}\n'.format(self.ciaddr)
        string += 'yiaddr: {:s}\n'.format(self.yiaddr)
        string += 'siaddr: {:s}\n'.format(self.siaddr)
        string += 'giaddr: {:s}\n'.format(self.giaddr)
        string += 'chaddr: {:s}\n'.format(':'.join('{:02x}'.format(c) for c in self.chaddr))
        string += 'magic: {:s}\n'.format(':'.join('{:02x}'.format(c) for c in self.magic))

        for opt in self.options:
            string += 'Option {:d}: {:s}\n'.format(int(opt), str(self.options[opt]))

        return string

    #
    # Decode a data stream into a Dhcp Packet.
    #
    def decode(self, data):
        self.op = DhcpPacket.decode_value('int8', data[0:1])
        self.htype = DhcpPacket.decode_value('int8', data[1:2])
        self.hlen = DhcpPacket.decode_value('int8', data[2:3])
        self.hops = DhcpPacket.decode_value('int8', data[3:4])
        self.xid = DhcpPacket.decode_value('*oct', data[4:8])
        self.secs = DhcpPacket.decode_value('int16', data[8:10])
        self.flags = DhcpPacket.decode_value('int16', data[10:12])
        self.ciaddr = DhcpPacket.decode_value('ip', data[12:16])
        self.yiaddr = DhcpPacket.decode_value('ip', data[16:20])
        self.siaddr = DhcpPacket.decode_value('ip', data[20:24])
        self.giaddr = DhcpPacket.decode_value('ip', data[24:28])
        self.chaddr = DhcpPacket.decode_value('*oct', data[28:(28+self.hlen)])
        self.magic = DhcpPacket.decode_value('*oct', data[236:240])

        opts = data[240:]
        self.options = { }
        while len(opts):
            code = struct.unpack('=B', opts[0:1])[0]
            if code == 255:
                break
            length = struct.unpack('=B', opts[1:2])[0]
            if code in DHCP_TYPES:
                fmt = DHCP_TYPES[code]
            else:
                fmt = '*oct'
            self.options[code] = DhcpPacket.decode_value(fmt, opts[2:2+length])
            opts = opts[2+length:]

    #
    # Encode the packet into a data stream.
    #
    def encode(self):
        data = DhcpPacket.encode_value('int8', self.op)
        data += DhcpPacket.encode_value('int8', self.htype)
        data += DhcpPacket.encode_value('int8', self.hlen)
        data += DhcpPacket.encode_value('int8', self.hops)
        data += DhcpPacket.encode_value('*oct', self.xid)
        data += DhcpPacket.encode_value('int16', self.secs)
        data += DhcpPacket.encode_value('int16', self.flags)
        data += DhcpPacket.encode_value('ip', self.ciaddr)
        data += DhcpPacket.encode_value('ip', self.yiaddr)
        data += DhcpPacket.encode_value('ip', self.siaddr)
        data += DhcpPacket.encode_value('ip', self.giaddr)
        data += DhcpPacket.encode_value('*oct', (self.chaddr + ([0] * (208 - len(self.chaddr)))))
        data += DhcpPacket.encode_value('*oct', self.magic)

        for opt in self.options:
            if opt in DHCP_TYPES:
                fmt = DHCP_TYPES[opt]
            else:
                fmt = '*B'

            dat = DhcpPacket.encode_value(fmt, self.options[opt])
            data += struct.pack('=BB', opt, len(dat))
            data += dat

        data += struct.pack('=BB', 255, 0)

        return data

    #
    # Return a new DhcpPacket that is an ACK to this one.
    #
    def newAckPacket(self):
        ack = DhcpPacket()
        ack.htype = self.htype
        ack.xid = self.xid
        ack.ciaddr = self.ciaddr
        ack.flags = self.flags
        ack.giaddr = self.giaddr
        ack.chaddr = self.chaddr
        ack.op = 2
        ack.hlen = self.hlen
        ack.options[OPTION_MESSAGE_TYPE] = MESSAGE_ACK

        return ack
