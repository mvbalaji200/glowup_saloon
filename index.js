const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");

const siteDirectory = path.join(__dirname, "index");
const contentTypes = {
  ".html": "text/html; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8"
};

const server = http.createServer((request, response) => {
  const pathname = new URL(request.url, "http://localhost").pathname;
  const requestedFile = pathname === "/" ? "index.html" : pathname.slice(1);
  const filePath = path.resolve(siteDirectory, requestedFile);

  if (!filePath.startsWith(`${siteDirectory}${path.sep}`)) {
    response.writeHead(403).end("Forbidden");
    return;
  }

  fs.readFile(filePath, (error, content) => {
    if (error) {
      response.writeHead(error.code === "ENOENT" ? 404 : 500).end("Not found");
      return;
    }

    response.writeHead(200, {
      "Content-Type": contentTypes[path.extname(filePath)] || "application/octet-stream"
    });
    response.end(content);
  });
});

const port = Number(process.env.PORT) || 3000;
server.listen(port, () => {
  console.log(`GlowUP is available at http://localhost:${port}`);
});