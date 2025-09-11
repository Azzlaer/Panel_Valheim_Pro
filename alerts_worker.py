# -*- coding: utf-8 -*-
import json, time, socket, struct, sys
from pathlib import Path
from datetime import datetime

# === CONFIG: ajusta estos valores a los de tu config.php ===
RCON_HOST = "127.0.0.1"
RCON_PORT = 2974          # normalmente puerto del juego + 2
RCON_PASS = "35027595"

ALERTS_FILE = "alerts.json"

SRVDATA_MULTI_LIMIT = 4096

class RconClient:
    TYPE_RESP = 0
    TYPE_CMD  = 2
    TYPE_AUTH = 3
    def __init__(self, host, port, timeout=4):
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.sock.settimeout(timeout)
        self.sock.connect((host, int(port)))
        self.req_id = 0
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
            if not hdr: break
            length = struct.unpack('<i', hdr)[0]
            body = self._recv_exact(length)
            if not body: break
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
            if not chunk: return None
            buf += chunk
        return buf
    def login(self, password):
        _ = self._send_packet(self.TYPE_AUTH, password)
        return True
    def command(self, cmd):
        return self._send_packet(self.TYPE_CMD, cmd)

def load_jobs(path: Path):
    if not path.exists(): return []
    try:
        return json.loads(path.read_text(encoding='utf-8'))
    except Exception:
        return []

def save_jobs(path: Path, jobs):
    path.write_text(json.dumps(jobs, ensure_ascii=False, indent=2), encoding='utf-8')

def main():
    base = Path(__file__).resolve().parent
    jf = base / ALERTS_FILE
    jobs = load_jobs(jf)
    if not isinstance(jobs, list): jobs = []

    now = int(time.time())
    any_changed = False

    # Conectar RCON (intentamos una vez para todos los envíos pendientes)
    rc = None
    try:
        rc = RconClient(RCON_HOST, RCON_PORT, timeout=4)
        rc.login(RCON_PASS)
    except Exception as e:
        rc = None
        print(f"[{datetime.now()}] [ERROR] RCON: {e}")

    for j in jobs:
        if j.get('status') != 'active':
            continue
        next_ts = int(j.get('next_run_ts') or 0)
        left    = int(j.get('repeats_left') or 0)
        if left <= 0:
            j['status'] = 'done'
            any_changed = True
            continue
        if now >= next_ts:
            cmd = j.get('command','')
            if cmd and rc:
                try:
                    rc.command(cmd)
                except Exception as e:
                    print(f"[{datetime.now()}] [WARN] Falló envío RCON: {e}")
            # actualizar job
            j['repeats_left'] = max(0, left - 1)
            j['last_run_ts']  = now
            if j['repeats_left'] > 0:
                j['next_run_ts'] = now + int(j.get('interval') or 60)
            else:
                j['status'] = 'done'
            any_changed = True

    if rc: rc.close()
    if any_changed:
        save_jobs(jf, jobs)

if __name__ == '__main__':
    main()
