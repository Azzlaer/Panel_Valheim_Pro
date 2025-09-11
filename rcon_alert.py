# -*- coding: utf-8 -*-
import argparse
import socket
import struct
import time

SRVDATA_MULTI_LIMIT = 4096

class RconClient:
    TYPE_RESP = 0
    TYPE_CMD  = 2
    TYPE_AUTH = 3
    def __init__(self, host, port, timeout=4):
        self.host = host
        self.port = int(port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.sock.settimeout(timeout)
        self.req_id = 0
        self.sock.connect((self.host, self.port))
    def close(self):
        try: self.sock.close()
        except: pass
    def _send_packet(self, ptype, payload):
        self.req_id += 1
        req_id = self.req_id
        body = struct.pack('<ii', req_id, ptype) + payload.encode('utf-8') + b'\x00\x00'
        pkt  = struct.pack('<i', len(body)) + body
        self.sock.sendall(pkt)
        data = b''
        while True:
            hdr = self._recv_exact(4)
            if not hdr:
                break
            length = struct.unpack('<i', hdr)[0]
            body = self._recv_exact(length)
            if not body:
                break
            rid, rtype = struct.unpack('<ii', body[:8])
            payload = body[8:-2]
            data += payload
            if len(payload) == 0 or len(payload) < (SRVDATA_MULTI_LIMIT - 16):
                break
        return data.decode('utf-8', errors='replace')
    def _recv_exact(self, n):
        buf = b''
        while len(buf) < n:
            chunk = self.sock.recv(n - len(buf))
            if not chunk:
                return None
            buf += chunk
        return buf
    def login(self, password):
        _ = self._send_packet(self.TYPE_AUTH, password)
        return True
    def command(self, cmd):
        return self._send_packet(self.TYPE_CMD, cmd)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--host', required=True)
    ap.add_argument('--port', required=True, type=int)
    ap.add_argument('--password', required=True)
    ap.add_argument('--command', required=True, help='Comando RCON a enviar en cada intervalo (texto tal cual)')
    ap.add_argument('--interval', required=True, type=int, help='Segundos entre envíos')
    ap.add_argument('--repeats', required=True, type=int, help='Número de repeticiones')
    args = ap.parse_args()

    interval = max(1, int(args.interval))
    repeats  = max(1, int(args.repeats))

    try:
        rc = RconClient(args.host, args.port, timeout=4)
        rc.login(args.password)
    except Exception as e:
        print(f'[ERROR] RCON: {e}', flush=True)
        return

    for i in range(repeats):
        try:
            rc.command(args.command)
        except Exception as e:
            print(f'[WARN] Falló el envío RCON: {e}', flush=True)
        if i < repeats - 1:
            time.sleep(interval)

    rc.close()

if __name__ == '__main__':
    main()
