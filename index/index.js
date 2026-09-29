const http = require('http');
const fs = require('fs');
const path = require('path');

const hostname = 'localhost';
const port = 3000;

const server = http.createServer((req, res) => {
  const isCss = req.url === '/style.css';
  res.setHeader('Content-Type', isCss ? 'text/css' : 'text/html');
  res.end(fs.readFileSync(path.join(__dirname, isCss ? 'style.css' : 'index.html')));
});

server.listen(port, hostname, () => {
  console.log(`Server running at http://${hostname}:${port}/`);
});