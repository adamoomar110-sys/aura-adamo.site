#!/usr/bin/env node
/**
 * ================================================================
 * Deploy FTP a DonWeb/Ferozo — PROYECTO: Aura Adamo Site
 * ================================================================
 * ⚠️  ESTE SCRIPT SOLO TOCA LA CARPETA: public_html/aura-adamo/
 *     - Landing Aura Adamo:  public_html/aura-adamo/
 *     - Odonto Merlo Demo:   public_html/aura-adamo/odonto/
 *     - Gestión de Flota:    public_html/aura-adamo/flota/
 *
 *     NO modifica nada de public_html/ (raíz de l1deres.site)
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
const REPOS_ROOT = path.resolve(ROOT, "..");

const CONFIG = {
  host: "a0170001.ferozo.com",
  user: "a0170001",
  password: "AuraFTP2025@aura",
  secure: true,
  secureOptions: { rejectUnauthorized: false },
  remoteRoot: "/public_html/aura-adamo",
};

const LANDING_FILES = [
  "index.html",
  "style.css",
  "script.js",
  "libros.html",
  "descarga-exitosa.html",
  "biblioteca.html",
  "campana_instagram.html",
  "reels.html",
  ".htaccess"
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

function getAllProductionFiles(dirPath, arrayOfFiles = []) {
  const files = fs.readdirSync(dirPath);
  files.forEach(function(file) {
    const fullPath = path.join(dirPath, file);
    if (fs.statSync(fullPath).isDirectory()) {
      arrayOfFiles = getAllProductionFiles(fullPath, arrayOfFiles);
    } else {
      if (!file.startsWith("__next.") && !file.endsWith(".map") && !file.endsWith(".txt")) {
        arrayOfFiles.push(fullPath);
      }
    }
  });
  return arrayOfFiles;
}

async function uploadDirectoryFiles(baseLocalDir, baseRemoteDir) {
  const allFiles = getAllProductionFiles(baseLocalDir);
  console.log(`  📦 Total de archivos a sincronizar: ${allFiles.length}`);

  let client = await connectFTP();
  let currentRemoteDir = "";

  for (let i = 0; i < allFiles.length; i++) {
    const local = allFiles[i];
    const rel = path.relative(baseLocalDir, local).replace(/\\/g, "/");
    const remoteFilePath = `${baseRemoteDir}/${rel}`;
    const targetDir = path.dirname(remoteFilePath).replace(/\\/g, "/");
    const fileName = path.basename(local);

    let uploaded = false;
    let attempts = 0;

    while (!uploaded && attempts < 3) {
      attempts++;
      try {
        if (currentRemoteDir !== targetDir) {
          await client.ensureDir(targetDir);
          currentRemoteDir = targetDir;
        }
        await client.uploadFrom(local, fileName);
        uploaded = true;
        process.stdout.write(`  ⏳ [${i + 1}/${allFiles.length}] ${rel}                       \r`);
      } catch (err) {
        try { client.close(); } catch (_) {}
        await new Promise(r => setTimeout(r, 1000));
        client = await connectFTP();
        currentRemoteDir = "";
      }
    }
  }
  try { client.close(); } catch (_) {}
  console.log(`\n  ✅ Subida completada: ${allFiles.length} archivos.`);
}

async function deployLanding() {
  console.log("🌐 [1/5] DEPLOY LANDING AURA ADAMO (public_html/aura-adamo/)");
  console.log("─".repeat(50));
  const client = await connectFTP();
  try {
    await client.ensureDir(CONFIG.remoteRoot);
    for (const file of LANDING_FILES) {
      const local = path.join(ROOT, file);
      if (!fs.existsSync(local)) continue;
      await client.uploadFrom(local, file);
      const size = (fs.statSync(local).size / 1024).toFixed(1);
      console.log(`  ✅ ${file} (${size} KB)`);
    }
  } finally {
    client.close();
  }
}

async function deployOdonto() {
  console.log("\n🦷 [2/5] DEPLOY ODONTO MERLO (public_html/aura-adamo/odonto/)");
  console.log("─".repeat(50));
  const odontoDist = path.join(REPOS_ROOT, "odonto_merlo", "dist");
  if (!fs.existsSync(odontoDist)) {
    console.log("  ⚠️ Carpeta dist de odonto_merlo no encontrada (omitiendo).");
    return;
  }
  await uploadDirectoryFiles(odontoDist, `${CONFIG.remoteRoot}/odonto`);
}

async function deployFlota() {
  console.log("\n🚗 [3/5] DEPLOY GESTIÓN DE FLOTA (public_html/aura-adamo/flota/)");
  console.log("─".repeat(50));
  const flotaOut = path.join(REPOS_ROOT, "cheq-flota-de-autos", "web", "out");
  const flotaRoot = path.join(REPOS_ROOT, "cheq-flota-de-autos");
  
  if (fs.existsSync(flotaOut)) {
    await uploadDirectoryFiles(flotaOut, `${CONFIG.remoteRoot}/flota`);
  }

  // Subir endpoints PHP backend
  const phpFiles = fs.readdirSync(flotaRoot).filter(f => f.endsWith(".php"));
  if (phpFiles.length > 0) {
    console.log(`\n  ⚡ Subiendo ${phpFiles.length} endpoints PHP backend...`);
    const client = await connectFTP();
    try {
      await client.ensureDir(`${CONFIG.remoteRoot}/flota`);
      for (const php of phpFiles) {
        const localPhp = path.join(flotaRoot, php);
        await client.uploadFrom(localPhp, php);
        console.log(`  ✅ flota/${php}`);
      }
    } finally {
      client.close();
    }
  }
}

async function deployApi() {
  console.log("\n⚡ [4/5] DEPLOY BACKEND API AURA (public_html/aura-adamo/aura-api/)");
  console.log("─".repeat(50));
  const apiDir = path.join(ROOT, "aura-api");
  if (!fs.existsSync(apiDir)) return;
  const client = await connectFTP();
  try {
    const remoteApi = `${CONFIG.remoteRoot}/aura-api`;
    await client.ensureDir(remoteApi);
    const files = fs.readdirSync(apiDir).filter(f => f.endsWith(".php"));
    for (const file of files) {
      const local = path.join(apiDir, file);
      await client.uploadFrom(local, file);
      const size = (fs.statSync(local).size / 1024).toFixed(1);
      console.log(`  ✅ aura-api/${file} (${size} KB)`);
    }
  } finally {
    client.close();
  }
}

async function deploy() {
  try {
    console.log("🚀 DEPLOY ECOSISTEMA AURA ADAMO → aura-adamo.site (DonWeb/Ferozo)\n");
    await deployLanding();
    await deployApi();
    await deployFlota();
    await deployOdonto();
    console.log("\n🎉 DESPLIEGUE A DONWEB COMPLETADO CON ÉXITO");
    console.log("─".repeat(50));
    console.log("  🌐 Landing Principal:  https://aura-adamo.site");
    console.log("  🚗 Gestión de Flota:   https://aura-adamo.site/flota/");
    console.log("  📚 Catálogo Libros:    https://aura-adamo.site/libros.html");
    console.log("  ⚡ API Backend:        https://aura-adamo.site/api/status.php");
  } catch (err) {
    console.error("\n❌ ERROR:", err.message);
    process.exit(1);
  }
}

deploy();
