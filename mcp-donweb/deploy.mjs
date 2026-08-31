#!/usr/bin/env node
/**
 * ================================================================
 * Deploy FTP a DonWeb/Ferozo — PROYECTO: Aura Adamo Site
 * ================================================================
 * ⚠️  ESTE SCRIPT SOLO TOCA LA CARPETA: public_html/aura-adamo/
 *     NO modifica nada de public_html/ (raíz de l1deres.site)
 *     NO toca admin/, cliente/, api/ (son de L1deres)
 *
 * FTP:  a0170001 @ a0170001.ferozo.com
 * ================================================================
 */
import * as ftp from "basic-ftp";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, "..");

const CONFIG = {
  host: "a0170001.ferozo.com",
  user: "a0170001",
  password: "AuraFTP2025@aura",
  secure: true,
  secureOptions: { rejectUnauthorized: false },
  // ⚠️ RAÍZ de Aura Adamo — NO cambiar a "public_html"
  // (eso pisaría a L1deres AutoWash)
  remoteRoot: "public_html/aura-adamo",
};

// ⚠️ Solo archivos de la Landing de Aura Adamo
// NO incluir archivos de L1deres aquí
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
  console.log("🌐 DEPLOY LANDING AURA ADAMO (public_html/aura-adamo/)");
  console.log("─".repeat(50));
  // Asegurar que la carpeta existe
  const setupClient = await connectFTP();
  try { await setupClient.ensureDir(CONFIG.remoteRoot); }
  finally { setupClient.close(); }

  for (const file of LANDING_FILES) {
    const local = path.join(ROOT, file);
    if (!fs.existsSync(local)) { console.log(`  ⏭️  ${file} (no existe localmente, omitido)`); continue; }
    const remote = `${CONFIG.remoteRoot}/${file}`;
    const client = await connectFTP();
    try {
      await client.uploadFrom(local, remote);
      const size = (fs.statSync(local).size / 1024).toFixed(1);
      console.log(`  ✅ ${file} (${size} KB)`);
    } catch (err) {
      console.log(`  ❌ ${file} → Error: ${err.message}`);
    } finally { client.close(); }
  }
}

async function deploy() {
  try {
    console.log("🚀 DEPLOY AURA ADAMO → aura-adamo.site\n");
    console.log("⚠️  Este script NO toca public_html/ (raíz de l1deres.site)\n");
    await deployLanding();
    console.log("\n🎉 DESPLIEGUE COMPLETADO CON ÉXITO");
    console.log("─".repeat(50));
    console.log("  🌐 Sitio en vivo: https://aura-adamo.site");
  } catch (err) {
    console.error("\n❌ ERROR DE CONEXIÓN FTP:", err.message);
    process.exit(1);
  }
}

deploy();
