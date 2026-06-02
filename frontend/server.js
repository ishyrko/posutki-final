/**
 * cPanel Node.js entrypoint (Setup Node.js App → Application startup file: server.js).
 * Docker/production compose keeps using `npm run start` → `next start`.
 *
 * Proxies /uploads at runtime via BACKEND_INTERNAL_URL (next.config rewrites are build-time only).
 */
const http = require("http");
const https = require("https");
const { URL } = require("url");
const next = require("next");

const port = parseInt(process.env.PORT || "3000", 10);
const hostname = process.env.HOSTNAME || "0.0.0.0";
const app = next({ dev: false });
const handle = app.getRequestHandler();

function getBackendOrigin() {
  const raw = process.env.BACKEND_INTERNAL_URL?.trim();
  if (!raw) {
    return null;
  }
  return raw.replace(/\/+$/, "");
}

function isUploadsRequest(pathname) {
  return pathname === "/uploads" || pathname.startsWith("/uploads/");
}

function proxyToBackend(req, res, backendOrigin) {
  const target = new URL(req.url || "/", backendOrigin);
  const transport = target.protocol === "https:" ? https : http;

  const proxyReq = transport.request(
    target,
    {
      method: req.method,
      headers: {
        ...req.headers,
        host: target.host,
      },
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode ?? 502, proxyRes.headers);
      proxyRes.pipe(res);
    },
  );

  proxyReq.on("error", (err) => {
    console.error("Backend proxy error:", err.message, target.href);
    if (!res.headersSent) {
      res.statusCode = 502;
      res.end("Bad Gateway");
    }
  });

  req.pipe(proxyReq);
}

app
  .prepare()
  .then(() => {
    const backendOrigin = getBackendOrigin();
    if (backendOrigin) {
      console.log(`Upload proxy: ${backendOrigin}/uploads/*`);
    } else {
      console.warn(
        "BACKEND_INTERNAL_URL is not set — /uploads will not be proxied to the API server",
      );
    }

    createServer((req, res) => {
      const pathname = new URL(req.url || "/", "http://localhost").pathname;
      if (backendOrigin && isUploadsRequest(pathname)) {
        proxyToBackend(req, res, backendOrigin);
        return;
      }
      handle(req, res);
    }).listen(port, hostname, () => {
      console.log(`Next.js ready on http://${hostname}:${port}`);
    });
  })
  .catch((err) => {
    console.error("Failed to start Next.js:", err);
    process.exit(1);
  });
