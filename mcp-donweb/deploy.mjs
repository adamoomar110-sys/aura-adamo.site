#!/usr/bin/env node
/**
 * Deploy FTP a DonWeb/Ferozo
 * Proyecto: Aura Adamo Site
 */
import * as ftp from "basic-ftp";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, "..");

const CONFIG = {
  host: "a0170001.ferozo.com", // Validar si es el mismo host
  user: "a0170001",
  password: "AuraFTP2025@aura",
  secure: true,
  secureOptions: { rejectUnauthorized: false },
  remoteRoot: "public_html/aura-adamo",
};

// Archivos Landing Comercial (Raíz)
const LANDING_FILES = [
  "index.html",
  "style.css",
  "script.js",
];

async function connectFTP() {
  const client = new ftp.Client();
  client.ftp.verbose = false;
  await client.access({
    host: CONFIG.host,
    user: CONFIG.user,
    password: CONFIG.password,
    secure: CONFIG.secure,
    secureOptions: CONFIG.secureOptions,
  });
  return client;
}

async function deployLanding() {
  console.log("🌐 DEPLOY LANDING AURA ADAMO (public_html/)");
  console.log("─".repeat(45));
  for (const file of LANDING_FILES) {
    const local = path.join(ROOT, file);
    if (!fs.existsSync(local)) continue;
    const remote = `${CONFIG.remoteRoot}/${file}`;
    const client = await connectFTP();
    try {
      await client.uploadFrom(local, remote);
      const size = (fs.statSync(local).size / 1024).toFixed(1);
      console.log(`  ✅ ${file} (${size} KB)`);
    } catch (err) {
      console.log(`  ❌ ${file} → Error: ${err.message}`);
    } finally {
      client.close();
    }
  }
}

async function deploy() {
  try {
    console.log("🚀 INICIANDO DESPLIEGUE EN DONWEB (aura-adamo.site)\n");
    await deployLanding();

    console.log("\n🎉 DESPLIEGUE COMPLETADO CON ÉXITO");
    console.log("─".repeat(50));
    console.log("  🌐 Sitio en vivo: https://aura-adamo.site");
  } catch (err) {
    console.error("\n❌ ERROR DE CONEXIÓN FTP:");
    console.error(err.message);
    process.exit(1);
  }
}

deploy();
