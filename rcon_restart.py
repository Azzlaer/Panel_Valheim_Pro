# -*- coding: utf-8 -*-
import argparse
import json
import socket
import struct
import subprocess
import sys
import time
from pathlib import Path

"""
Cuenta regresiva y reinicio con avisos RCON:
- Anuncia por RCON cuando faltan 5 min, 1 min, 10 s.
- Opcional: envía 'save' antes de apagar.
- Detiene el servidor (taskkill /IM valheim_server.exe) y lo inicia a los 15s usando servers.json.

Requiere el mod ValheimRcon (protocolo RCON binario).
"""

# ----------- RCON cliente (protocolo tipo Source) -----------
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

        # body = [id(4)][type(4)][payload][\x00][\x00]
        body = struct.pack('<ii', req_id, ptype) + payload.encode('utf-8') + b'\x00\x00'
        pkt  = struct.pack('<i', len(body)) + body
        self.sock.sendall(pkt)

        # Read response(s)
        data = b''
        while True:
            hdr = self._recv_exact(4)
            if not hdr:
                break
            length = struct.unpack('<i', hdr)[0]
            body = self._recv_exact(length)
            if not body:
                break
            # parse
            rid, rtype = struct.unpack('<ii', body[:8])
            payload = body[8:-2]  # strip 2 null bytes
            data += payload

            # heurística: romper si payload es corto (no hay más partes)
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
        # algunos servidores no devuelven texto útil aquí
        return True

    def command(self, cmd):
        return self._send_packet(self.TYPE_CMD, cmd)

# ----------- helpers sistema -----------
def stop_server(exe_name):
    # Windows: taskkill /F /IM valheim_server.exe
    try:
        subprocess.run(['taskkill', '/F', '/IM', exe_name], check=False, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except Exception as e:
        print(f"[WARN] No se pudo detener con taskkill: {e}", flush=True)

def start_server(servers_json_path, server_id):
    # Leer servers.json y arrancar el server indicado
    p = Path(servers_json_path)
    srv_id = int(server_id)
    data = json.loads(p.read_text(encoding='utf-8'))
    found = None
    for s in data:
        if int(s.get('id', 0)) == srv_id:
            found = s
            break
    if not found:
        print("[ERROR] Servidor no encontrado en servers.json", flush=True)
        return
    path = found.get('path', '')
    params = found.get('params', '')
    if not path:
        print("[ERROR] Sin 'path' en servers.json", flush=True)
        return
    # Windows: launch detached with start
    cmd = f'start "ValheimServer{srv_id}" "{path}" {params}'
    subprocess.Popen(['cmd', '/c', cmd], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

def format_time(sec):
    h = sec // 3600
    m = (sec % 3600) // 60
    s = sec % 60
    parts = []
    if h: parts.append(f"{h}h")
    if m: parts.append(f"{m}m")
    if s or not parts: parts.append(f"{s}s")
    return " ".join(parts)

# ----------- main -----------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--host', required=True)
    ap.add_argument('--port', required=True, type=int)
    ap.add_argument('--password', required=True)
    ap.add_argument('--delay', required=True, type=int, help="Segundos totales de cuenta atrás")
    ap.add_argument('--servers-json', required=True)
    ap.add_argument('--server-id', required=True, type=int)
    ap.add_argument('--exe-name', default='valheim_server.exe')
    ap.add_argument('--save-before', default='0')
    args = ap.parse_args()

    delay = max(0, int(args.delay))
    save_before = str(args.save_before) in ('1','true','True','yes','on')

    # Conectar RCON
    try:
        rc = RconClient(args.host, args.port, timeout=4)
        rc.login(args.password)
    except Exception as e:
        print(f"[ERROR] RCON: {e}", flush=True)
        rc = None

    # Hitos a anunciar (en segundos restantes)
    milestones = {300: "Server restart in 5 minutes!",
                  60:  "Server restart in 1 minute!",
                  10:  "Server restart in 10 seconds"}

    # Loop cuenta atrás
    last_announced = set()
    t = delay
    while t > 0:
        if t in milestones and rc:
            msg = milestones[t]
            try:
                # Usa el comando que tu mod acepta; comúnmente 'showMessage <texto>'
                rc.command(f'showMessage {msg}')
            except Exception as e:
                print(f"[WARN] RCON announce failed ({t}s): {e}", flush=True)
            last_announced.add(t)
        time.sleep(1)
        t -= 1

    # Antes de apagar, guardar si corresponde
    if save_before and rc:
        try:
            rc.command('save')
            # pequeña espera para que termine el guardado
            time.sleep(2)
        except Exception as e:
            print(f"[WARN] RCON save failed: {e}", flush=True)

    # Apagar servidor
    stop_server(args.exe_name)
    time.sleep(15)

    # Iniciar servidor
    start_server(args.servers_json, args.server_id)

    # Mensaje post-reinicio (opcional)
    try:
        # dar unos segundos por si tarda en levantar RCON; suele tardar más, esto es best-effort
        time.sleep(10)
        if rc:
            # reintentar enviar un aviso (si el socket anterior sigue vivo no servirá; solo informativo)
            rc.command('showMessage Server restarted.')
    except Exception:
        pass

    if rc:
        rc.close()

if __name__ == '__main__':
    main()
