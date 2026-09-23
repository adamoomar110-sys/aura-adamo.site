import * as ftp from "basic-ftp";
import fs from "fs";
import path from "path";

const CONFIG = {
  host: "a0170001.ferozo.com",
  user: "a0170001",
  password: "AuraFTP2025@aura",
  secure: true,
  secureOptions: { rejectUnauthorized: false },
  remoteRoot: "/public_html/aura-adamo/descargas",
};

const localDir = "C:/Users/trabajo ia/.gemini/antigravity-ide/scratch/descargas_servidor";

async function run() {
  const client = new ftp.Client();
  client.ftp.verbose = false;

  try {
    console.log("Conectando a FTPS en DonWeb (a0170001.ferozo.com)...");
    await client.access(CONFIG);
    await client.ensureDir(CONFIG.remoteRoot);

    const files = fs.readdirSync(localDir).filter(f => f.endsWith('.pdf') || f.endsWith('.zip'));
    console.log(`Subiendo ${files.length} archivos (PDFs y ZIPs de colecciones)...`);

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const localFilePath = path.join(localDir, file);
      const remoteFilePath = `${CONFIG.remoteRoot}/${file}`;
      const sizeMB = (fs.statSync(localFilePath).size / 1024 / 1024).toFixed(2);
      process.stdout.write(`[${i+1}/${files.length}] Subiendo ${file} (${sizeMB} MB)... `);
      await client.uploadFrom(localFilePath, remoteFilePath);
      console.log("✅ OK");
    }

    console.log("\n🎉 ¡Todos los PDFs y ZIPs de Aura fueron subidos exitosamente a DonWeb!");
  } catch (err) {
    console.error("Error durante la subida:", err);
  } finally {
    client.close();
  }
}

run();
