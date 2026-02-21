const net = require('net');
const https = require('https');

const CONFIG = {
    PRINTER_IP: '192.168.1.43',      // Cambia por la IP de tu impresora
    PRINTER_PORT: 9100,
    SERVER: 'pacasyadira.com',          // Sin https://
    SECRET: 'PacasYadira',        // La misma clave de los PHP
    INTERVAL: 3000
};

function request(path, callback) {
    https.get(`https://${CONFIG.SERVER}/${path}`, {
        headers: { 'Authorization': `Bearer ${CONFIG.SECRET}` }
    }, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            try { callback(JSON.parse(data)); }
            catch(e) { callback(null); }
        });
    }).on('error', () => callback(null));
}

function markDone(id) {
    request(`mark_done.php?id=${id}`, () => {
        console.log(`✅ Job #${id} completado`);
    });
}

function printJob(job) {
    const client = new net.Socket();

    client.connect(CONFIG.PRINTER_PORT, CONFIG.PRINTER_IP, () => {
        console.log(`🖨️  Imprimiendo job #${job.id}...`);
        client.write(job.zpl);
        client.destroy();
        markDone(job.id);
    });

    client.on('error', (err) => {
        console.log(`❌ Error en impresora: ${err.message}`);
    });
}

function fetchAndPrint() {
    request('get_queue.php', (jobs) => {
        if (!jobs || jobs.length === 0) return;
        console.log(`📋 ${jobs.length} trabajo(s) en cola`);
        jobs.forEach(job => printJob(job));
    });
}

console.log('🚀 Print Server iniciado');
console.log(`🖨️  Impresora: ${CONFIG.PRINTER_IP}:${CONFIG.PRINTER_PORT}`);
console.log(`🌐 Servidor: ${CONFIG.SERVER}`);
setInterval(fetchAndPrint, CONFIG.INTERVAL);