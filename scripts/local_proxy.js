const http = require('http');
const net = require('net');
const dns = require('dns');

dns.setServers(['8.8.8.8', '1.1.1.1']);

function resolveHost(hostname, cb) {
  if (net.isIP(hostname)) {
    return cb(null, hostname);
  }
  dns.resolve4(hostname, (err, addresses) => {
    if (err || !addresses || addresses.length === 0) {
      // fallback to resolve
      dns.resolve(hostname, (err2, addrs) => {
        if (err2 || !addrs || addrs.length === 0) {
          return cb(err2 || new Error('No address found'));
        }
        cb(null, addrs[0]);
      });
      return;
    }
    cb(null, addresses[0]);
  });
}

const server = http.createServer((req, res) => {
  try {
    const parsedUrl = new URL(req.url);
    resolveHost(parsedUrl.hostname, (err, address) => {
      if (err) {
        res.writeHead(502);
        return res.end(`DNS lookup failed for ${parsedUrl.hostname}: ${err.message}`);
      }
      const proxyReq = http.request({
        host: address,
        port: parsedUrl.port || 80,
        path: parsedUrl.pathname + parsedUrl.search,
        method: req.method,
        headers: { ...req.headers, host: parsedUrl.host }
      }, (proxyRes) => {
        res.writeHead(proxyRes.statusCode, proxyRes.headers);
        proxyRes.pipe(res);
      });
      proxyReq.on('error', (e) => {
        res.writeHead(502);
        res.end(`Proxy error: ${e.message}`);
      });
      req.pipe(proxyReq);
    });
  } catch (e) {
    res.writeHead(500);
    res.end(e.message);
  }
});

server.on('connect', (req, clientSocket, head) => {
  const parts = req.url.split(':');
  const hostname = parts[0];
  const port = parseInt(parts[1] || '443', 10);

  resolveHost(hostname, (err, address) => {
    if (err) {
      clientSocket.write('HTTP/1.1 502 Bad Gateway (DNS Failed)\r\n\r\n');
      clientSocket.end();
      return;
    }
    const serverSocket = net.connect(port, address, () => {
      clientSocket.write('HTTP/1.1 200 Connection Established\r\n\r\n');
      if (head && head.length > 0) serverSocket.write(head);
      serverSocket.pipe(clientSocket);
      clientSocket.pipe(serverSocket);
    });
    serverSocket.on('error', () => {
      clientSocket.end();
    });
    clientSocket.on('error', () => {
      serverSocket.end();
    });
  });
});

server.listen(8080, '127.0.0.1', () => {
  console.log('Local DNS-resolving proxy listening on 127.0.0.1:8080 (using Google/Cloudflare DNS)');
});
