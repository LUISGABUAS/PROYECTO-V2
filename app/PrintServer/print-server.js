const net  = require('net');
const https = require('https');
const fs   = require('fs');
const path = require('path');

// Log a archivo + consola
const LOG_FILE = path.join(path.dirname(process.execPath || __filename), 'print-server.log');
function log(msg) {
    const line = `[${new Date().toISOString()}] ${msg}\n`;
    process.stdout.write(line);
    try { fs.appendFileSync(LOG_FILE, line); } catch(_) {}
}

// Rotar log si supera 2 MB
try {
    if (fs.existsSync(LOG_FILE) && fs.statSync(LOG_FILE).size > 2 * 1024 * 1024) {
        fs.renameSync(LOG_FILE, LOG_FILE + '.old');
    }
} catch(_) {}

// Evitar que errores no capturados maten el proceso
process.on('uncaughtException', (err) => {
    log(`❌ Error no capturado: ${err.message}`);
});
process.on('unhandledRejection', (reason) => {
    log(`❌ Promesa rechazada: ${reason}`);
});

const CONFIG = {
    PRINTER_IP: '192.168.1.107',
    PRINTER_PORT: 9100,
    SERVER: 'pacasyadira.com',
    PATH: '/app/PrintServer',        // ← agrega esto
    SECRET: 'PacasYadira',
    INTERVAL: 3000
};

function request(path, callback) {
    const options = {
        hostname: CONFIG.SERVER,
        path: `${CONFIG.PATH}/${path}`,
        method: 'GET',
        headers: { 'Authorization': `Bearer ${CONFIG.SECRET}` }
    };

    log(`Consultando: https://${CONFIG.SERVER}${CONFIG.PATH}/${path}`);

    const req = https.request(options, (res) => {
        log(`Status: ${res.statusCode}`);
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            log(`Respuesta: ${data}`);
            try { callback(JSON.parse(data)); }
            catch(e) { 
                log(`Error parseando JSON: ${e.message}`);
                callback(null); 
            }
        });
    });

    req.on('error', (err) => {
        log(`Error de red: ${err.message} | Codigo: ${err.code}`);
        callback(null);
    });

    req.end();
}

function markDone(id) {
    request(`mark_done.php?id=${id}`, () => {
        log(`Job #${id} completado`);
    });
}

function printJob(job) {
    return new Promise((resolve) => {
        const client = new net.Socket();

        client.connect(CONFIG.PRINTER_PORT, CONFIG.PRINTER_IP, () => {
            log(`Imprimiendo job #${job.id}...`);
            client.write(job.zpl, () => {
                client.end();
            });
        });

        client.on('close', () => {
            markDone(job.id);
            resolve();
        });

        client.on('error', (err) => {
            log(`Error en impresora job #${job.id}: ${err.message}`);
            client.destroy();
            resolve();
        });
    });
}

function fetchAndPrint() {
    request('get_queue.php', async (jobs) => {
        if (jobs === null) {
            log('Error conectando al servidor');
            return;
        }
        if (!Array.isArray(jobs)) {
            log(`Respuesta inesperada del servidor: ${JSON.stringify(jobs)}`);
            return;
        }
        if (jobs.length === 0) {
            return;
        }
        log(`${jobs.length} trabajo(s) en cola`);
        for (const job of jobs) {
            await printJob(job);
        }
    });
}

log('Print Server iniciado');
log(`Impresora: ${CONFIG.PRINTER_IP}:${CONFIG.PRINTER_PORT}`);
log(`Servidor: ${CONFIG.SERVER}`);
setInterval(fetchAndPrint, CONFIG.INTERVAL);