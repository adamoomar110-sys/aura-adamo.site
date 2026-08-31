#!/usr/bin/env node
/**
 * ================================================================
 * Deploy FTP a DonWeb/Ferozo — PROYECTO: Aura Adamo Site
 * ================================================================
 * ⚠️  ESTE SCRIPT SOLO TOCA LA CARPETA: public_html/aura-adamo/
 *     - Landing Aura Adamo:  public_html/aura-adamo/
 *     - Odonto Merlo Demo:   public_html/aura-adamo/odonto/
 *     - Spinaz Garage Demo:  public_html/aura-adamo/spinaz/
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
  remoteRoot: "public_html/aura-adamo",
};

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

function getAllProductionFiles(dirPath, arrayOfFiles = []) {
  const files = fs.readdirSync(dirPath);
  files.forEach(function(file) {
    const fullPath = path.join(dirPath, file);
    if (fs.statSync(fullPath).isDirectory()) {
      arrayOfFiles = getAllProductionFiles(fullPath, arrayOfFiles);
    } else {
      // Filtrar archivos temporales o mapas
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
  console.log("🌐 [1/3] DEPLOY LANDING AURA ADAMO (public_html/aura-adamo/)");
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
  console.log("\n🦷 [2/3] DEPLOY ODONTO MERLO (public_html/aura-adamo/odonto/)");
  console.log("─".repeat(50));
  const odontoDist = path.join(REPOS_ROOT, "odonto_merlo", "dist");
  if (!fs.existsSync(odontoDist)) {
    console.log("  ⚠️ Carpeta dist de odonto_merlo no encontrada.");
    return;
  }
  await uploadDirectoryFiles(odontoDist, `${CONFIG.remoteRoot}/odonto`);
}

async function deploySpinaz() {
  console.log("\n🚗 [3/3] DEPLOY SPINAZ GARAGE (public_html/aura-adamo/spinaz/)");
  console.log("─".repeat(50));
  const spinazOut = path.join(REPOS_ROOT, "cheq-flota-de-autos", "web", "out");
  const spinazRoot = path.join(REPOS_ROOT, "cheq-flota-de-autos");
  
  if (!fs.existsSync(spinazOut)) {
    console.log("  ⚠️ Carpeta out de Spinaz Garage no encontrada.");
    return;
  }

  // 1. Subir frontend estático Next.js
  await uploadDirectoryFiles(spinazOut, `${CONFIG.remoteRoot}/spinaz`);

  // 2. Subir endpoints PHP backend
  const phpFiles = fs.readdirSync(spinazRoot).filter(f => f.endsWith(".php"));
  console.log(`\n  ⚡ Subiendo ${phpFiles.length} endpoints PHP backend...`);
  const client = await connectFTP();
  try {
    await client.ensureDir(`${CONFIG.remoteRoot}/spinaz`);
    for (const php of phpFiles) {
      const localPhp = path.join(spinazRoot, php);
      await client.uploadFrom(localPhp, php);
      console.log(`  ✅ spinaz/${php}`);
    }
  } finally {
    client.close();
  }
}

async function deploy() {
  try {
    console.log("🚀 DEPLOY COMPLETO ECOSISTEMA AURA ADAMO → aura-adamo.site\n");
    await deployLanding();
    await deployOdonto();
    await deploySpinaz();
    console.log("\n🎉 DESPLIEGUE COMPLETADO CON ÉXITO");
    console.log("─".repeat(50));
    console.log("  🌐 Landing Principal:  https://aura-adamo.site");
    console.log("  🦷 Odonto Merlo:      https://aura-adamo.site/odonto/");
    console.log("  🚗 Spinaz Garage:      https://aura-adamo.site/spinaz/");
  } catch (err) {
    console.error("\n❌ ERROR:", err.message);
    process.exit(1);
  }
}

deploy();
