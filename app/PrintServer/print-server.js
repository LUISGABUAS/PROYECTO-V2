const net = require('net');
const https = require('https');

const CONFIG = {
    PRINTER_IP: '192.168.1.21',
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

    console.log(`🔍 Consultando: https://${CONFIG.SERVER}/${path}`);

    const req = https.request(options, (res) => {
        console.log(`📡 Status: ${res.statusCode}`);
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
            console.log(`📦 Respuesta: ${data}`);
            try { callback(JSON.parse(data)); }
            catch(e) { 
                console.log(`❌ Error parseando JSON: ${e.message}`);
                callback(null); 
            }
        });
    });

    req.on('error', (err) => {
        console.log(`❌ Error de red: ${err.message} | Código: ${err.code}`);
        callback(null);
    });

    req.end();
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
        if (jobs === null) {
            console.log('❌ Error conectando al servidor');
            return;
        }
        if (jobs.length === 0) {
            console.log('⏳ Sin trabajos pendientes...');
            return;
        }
        console.log(`📋 ${jobs.length} trabajo(s) en cola`);
        jobs.forEach(job => printJob(job));
    });
}

console.log('🚀 Print Server iniciado');
console.log(`🖨️  Impresora: ${CONFIG.PRINTER_IP}:${CONFIG.PRINTER_PORT}`);
console.log(`🌐 Servidor: ${CONFIG.SERVER}`);
setInterval(fetchAndPrint, CONFIG.INTERVAL);