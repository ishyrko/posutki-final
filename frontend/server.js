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
// Do not use process.env.HOSTNAME — on Linux/cPanel it is the machine name (e.g. vh117), not a bind address.
const listenHost = process.env.LISTEN_HOST || "0.0.0.0";
const app = next({ dev: false });
const handle = app.getRequestHandler();

/** Hop-by-hop headers must not be forwarded (Node/http will break the response). */
const HOP_BY_HOP = new Set([
  "connection",
  "keep-alive",
  "proxy-authenticate",
  "proxy-authorization",
  "te",
  "trailers",
  "transfer-encoding",
  "upgrade",
]);

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

function getRequestProto(req) {
  const forwardedProto = req.headers["x-forwarded-proto"];
  if (typeof forwardedProto === "string") {
    return forwardedProto.split(",")[0].trim().toLowerCase();
  }
  return "http";
}

function shouldForceHttps() {
  return process.env.NODE_ENV === "production" && process.env.FORCE_HTTPS !== "0";
}

/** http:// → https:// (cPanel / reverse-proxy must send X-Forwarded-Proto on HTTPS requests). */
function redirectHttpToHttps(req, res) {
  if (!shouldForceHttps() || getRequestProto(req) === "https") {
    return false;
  }
  const host = req.headers.host;
  if (!host) {
    return false;
  }
  const location = `https://${host}${req.url || "/"}`;
  res.statusCode = 301;
  res.setHeader("Location", location);
  res.end();
  return true;
}

/** www.posutki.by → https://posutki.by */
function redirectWwwToCanonical(req, res) {
  const hostHeader = req.headers.host;
  if (!hostHeader) {
    return false;
  }
  const host = hostHeader.split(":")[0].toLowerCase();
  if (!host.startsWith("www.")) {
    return false;
  }
  const bareHost = host.slice(4);
  const proto = shouldForceHttps() ? "https" : getRequestProto(req) === "https" ? "https" : "http";
  const location = `${proto}://${bareHost}${req.url || "/"}`;
  res.statusCode = 301;
  res.setHeader("Location", location);
  res.end();
  return true;
}

function forwardResponseHeaders(proxyRes, res) {
  for (const [key, value] of Object.entries(proxyRes.headers)) {
    if (!key || HOP_BY_HOP.has(key.toLowerCase())) {
      continue;
    }
    if (value === undefined) {
      continue;
    }
    res.setHeader(key, value);
  }
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
      forwardResponseHeaders(proxyRes, res);
      res.statusCode = proxyRes.statusCode || 502;
      proxyRes.on("error", (err) => {
        console.error("Backend proxy response error:", err.message, target.href);
        if (!res.headersSent) {
          res.statusCode = 502;
          res.end("Bad Gateway");
        } else {
          res.destroy();
        }
      });
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

  if (req.method === "GET" || req.method === "HEAD") {
    proxyReq.end();
  } else {
    req.pipe(proxyReq);
  }
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

    http.createServer((req, res) => {
      if (redirectHttpToHttps(req, res)) {
        return;
      }
      if (redirectWwwToCanonical(req, res)) {
        return;
      }
      const pathname = new URL(req.url || "/", "http://localhost").pathname;
      if (isUploadsRequest(pathname)) {
        if (!backendOrigin) {
          res.statusCode = 404;
          res.end("Not Found");
          return;
        }
        proxyToBackend(req, res, backendOrigin);
        return;
      }
      handle(req, res);
    }).listen(port, listenHost, () => {
      console.log(`Next.js ready on http://${listenHost}:${port}`);
    });
  })
  .catch((err) => {
    console.error("Failed to start Next.js:", err);
    process.exit(1);
  });
