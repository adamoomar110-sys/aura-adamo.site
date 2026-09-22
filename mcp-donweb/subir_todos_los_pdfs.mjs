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

const localDir = "C:\\Users\\trabajo ia\\.gemini\\antigravity-ide\\scratch\\descargas_servidor";

async function run() {
  const client = new ftp.Client();
  client.ftp.verbose = false;

  try {
    console.log("Conectando a FTPS...");
    await client.access(CONFIG);
    await client.ensureDir(CONFIG.remoteRoot);

    const files = fs.readdirSync(localDir).filter(f => f.endsWith('.pdf'));
    console.log(`Subiendo ${files.length} archivos PDF...`);

    for (const file of files) {
      const localFilePath = path.join(localDir, file);
      const remoteFilePath = `${CONFIG.remoteRoot}/${file}`;
      console.log(`Subiendo: ${file}...`);
      await client.uploadFrom(localFilePath, remoteFilePath);
      console.log(`OK: ${file}`);
    }

    console.log("¡Todos los PDFs fueron subidos exitosamente!");
  } catch (err) {
    console.error("Error:", err);
  } finally {
    client.close();
  }
}

run();
