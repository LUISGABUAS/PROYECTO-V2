const net = require('net');
const https = require('https');

// Evitar que errores no capturados maten el proceso
process.on('uncaughtException', (err) => {
    console.log(`[${new Date().toISOString()}] ❌ Error no capturado: ${err.message}`);
});
process.on('unhandledRejection', (reason) => {
    console.log(`[${new Date().toISOString()}] ❌ Promesa rechazada: ${reason}`);
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
    const client = new net.Socket();

    client.connect(CONFIG.PRINTER_PORT, CONFIG.PRINTER_IP, () => {
        log(`Imprimiendo job #${job.id}...`);
        client.write(job.zpl);
        client.destroy();
        markDone(job.id);
    });

    client.on('error', (err) => {
        log(`Error en impresora: ${err.message}`);
    });
}

function fetchAndPrint() {
    request('get_queue.php', (jobs) => {
        if (jobs === null) {
            log('Error conectando al servidor');
            return;
        }
        if (jobs.length === 0) {
            // sin trabajos, silencioso para no llenar el log
            return;
        }
        log(`${jobs.length} trabajo(s) en cola`);
        jobs.forEach(job => printJob(job));
    });
}

function log(msg) {
    console.log(`[${new Date().toISOString()}] ${msg}`);
}

log('Print Server iniciado');
log(`Impresora: ${CONFIG.PRINTER_IP}:${CONFIG.PRINTER_PORT}`);
log(`Servidor: ${CONFIG.SERVER}`);
setInterval(fetchAndPrint, CONFIG.INTERVAL);