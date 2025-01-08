// Mengimpor modul-modul yang diperlukan
const pm2 = require("pm2");
const WebSocket = require("ws");
const { exec } = require("child_process");

// Membuat server WebSocket pada port 2222
const wss = new WebSocket.Server({ port: 2233 });
const monitoringInterval = 5000; // Interval pemeriksaan status setiap 5 detik
let status_HL7 = true;

const clients = []; // Array untuk menyimpan koneksi klien WebSocket
const kalibrasi = new Map(); // Map untuk menyimpan koneksi klien WebSocket dengan kunci tertentu
const infromasi = new Map();
const monitorClients = new Set(); // Set untuk menyimpan klien yang meminta monitoring
var status_report = "sukses";

// Konfigurasi untuk bot Telegram
const tele_id = ["710794517"]; // ID pengguna Telegram yang diizinkan
const TelegramBot = require("node-telegram-bot-api");

// Token API bot Telegram yang diperoleh dari BotFather
const token = "7406461639:AAHUsIwqhpw7d1t3ZyW1h11XSTEwdlwgI7M";

// Inisialisasi bot Telegram
const bot = new TelegramBot(token, { polling: true });

// Daftar perintah yang tersedia untuk bot Telegram
const commands = [
  { command: "/start", description: "Memulai Server HL7" },
  { command: "/restart", description: "Memulai ulang Server HL7" },
  { command: "/status", description: "Cek Status Server HL7" },
  { command: "/h", description: "Menampilkan bantuan" },
];

/**
 * Fungsi untuk mendapatkan status proses PM2 berdasarkan nama proses
 * @param {string} processName - Nama proses PM2 yang ingin dicek statusnya
 * @returns {Promise<string>} - Promise yang menghasilkan status proses
 */
function getPM2ProcessStatus(processName) {
  return new Promise((resolve, reject) => {
    // Menghubungkan ke PM2
    pm2.connect((err) => {
      if (err) {
        console.error(err);
        reject(err);
      }

      // Mendapatkan daftar proses PM2
      pm2.list((err, processList) => {
        if (err) {
          console.error(err);
          pm2.disconnect();
          reject(err);
        }

        // Mencari proses yang sesuai dengan nama yang diberikan
        const targetProcess = processList.find(
          (proc) => proc.name === processName
        );
        if (targetProcess) {
          const status = targetProcess.pm2_env.status;
          pm2.disconnect();
          resolve(status);
        } else {
          pm2.disconnect();
          reject(new Error(`Process ${processName} not found`));
        }
      });
    });
  });
}

/**
 * Fungsi untuk me-restart proses PM2 berdasarkan nama proses
 * @param {string} processName - Nama proses PM2 yang ingin di-restart
 */
function restartPM2Process(processName) {
  pm2.connect((err) => {
    if (err) {
      console.error(err);
      process.exit(2);
    }

    // Me-restart proses PM2
    pm2.restart(processName, (err, proc) => {
      if (err) {
        console.error(` :-> Error restarting ${processName}: ${err}`);
        pm2.disconnect();
        status_report = "error";
        return "error";
      }
      console.log(` :-> ${processName} has been restarted successfully`);
      pm2.disconnect();
      status_report = "sukses";

      return "sukses";
    });
  });
}

/**
 * Fungsi untuk memulai proses PM2 berdasarkan nama proses
 * @param {string} processName - Nama proses PM2 yang ingin dimulai
 */
function startPM2Process(processName) {
  pm2.connect((err) => {
    if (err) {
      console.error(err);
      process.exit(2);
    }

    // Memulai proses PM2
    pm2.start(processName, (err, proc) => {
      if (err) {
        console.error(` :-> Error starting ${processName}: ${err}`);
        pm2.disconnect();
        status_report = "error";
        return "error";
      }
      console.log(` :-> ${processName} has been started successfully`);
      pm2.disconnect();
      status_report = "sukses";
      return "sukses";
    });
  });
}

/**
 * Fungsi untuk mengirim status proses HL7Server_dev ke semua klien monitor
 * @returns {Promise<string>} - Promise yang menghasilkan status proses
 */
function kirimSemuaProses() {
  processName = "HL7Server_dev";
  return new Promise((resolve, reject) => {
    pm2.connect((err) => {
      if (err) {
        console.error(err);
        reject(err);
      }

      // Mendapatkan daftar proses PM2
      pm2.list((err, processList) => {
        if (err) {
          console.error(err);
          pm2.disconnect();
          reject(err);
        }

        // Mencari proses HL7Server_dev
        const targetProcess = processList.find(
          (proc) => proc.name === processName
        );
        if (targetProcess) {
          const status = targetProcess.pm2_env.status;
          pm2.disconnect();
          resolve(status);
          // console.log(status);
          if (status == "online") {
            // Mengirim status "connected" ke semua klien monitor
            monitorClients.forEach((client) => {
              client.send("#c");
            });
          } else {
            // Mengirim pesan ke grup Telegram jika server tidak terhubung
            bot.sendMessage(-4284927690, "Server Sedang Diskonek!!! /restart");
            // Mengirim status "disconnected" ke semua klien monitor
            monitorClients.forEach((client) => {
              client.send("#dc");
            });
          }
        } else {
          pm2.disconnect();
          reject(new Error(`Process ${processName} not found`));
        }
      });
    });
  });
}

/**
 * Event listener untuk koneksi WebSocket baru
 */
wss.on("connection", (ws) => {
  console.log(" :-> A new client connected!");
  clients.push(ws);

  // Mengirim pesan selamat datang ke klien
  ws.send("Masukkan perintah");

  // Menerima pesan dari klien
  ws.on("message", (message) => {
    console.log(" :-> received: %s", message);

    const parts = message.toString().split("|");
    const perintah = parts[0];
    // if (parts[0] === 'monitor') {
    //   console.log(`Command: ${parts[0]}, Data1: ${parts[1]}, Data2: ${parts[2]}`);
    //   ws.send(`Received monitor command with Data1: ${parts[1]} and Data2: ${parts[2]}`);
    // }
    // Menangani perintah yang diterima dari klien
    switch (perintah) {
      case "status_HL7":
        ws.send(status_HL7.toString());
        console.log(' :-> ' + status_HL7);
        break;
      case "list_c":
        console.log(` :-> Total connected clients: ${wss.clients.size}`);
        ws.send(`${wss.clients.size}`);
        break;
      case "status":
        getPM2ProcessStatus("HL7Server_dev")
          .then((status) => {
            console.log(` :-> Status of HL7Server_dev: ${status}`);
            ws.send(`${status}`);
          })
          .catch((err) => {
            ws.send(`${status}`);
            console.error(`:-> Error getting status of HL7Server_dev: ${err}`);
          });
        break;
      case "restart":
        restartPM2Process("HL7Server_dev");
        ws.send(status_report);
        break;
      case "start":
        startPM2Process("HL7Server_dev");
        ws.send(status_report);
        break;
      case "monitor":
        monitorClients.add(ws);
        ws.send("Anda akan menerima laporan monitor");
        break;
      case "kalibrasi":
        kalibrasi.set(parts[1].toString(), ws);
        ws.send(`proses`);
        // console.log(kalibrasiKeys);
        // ws.send(kalibrasi);

        break;
      case "recived":
        if (kalibrasi.has(parts[1].toString())) {
          kalibrasi.get(parts[1]).send('true');
          console.log(" :-> ditemukan");
        }
        if (infromasi.has(parts[1].toString())) {
          infromasi.get(parts[1]).send('masuk');
          console.log(" :-> masuk");
        }
        break;
      case "hapuskalibrasi":
        kalibrasi.delete(parts[1].toString());
        console.log(' :-> selesai');
        ws.send(`selesai`);
        break;
      case "informasi":
        infromasi.set(parts[1].toString(), ws);
        ws.send(`proses`);
        
        break;
      default:
        ws.send("perintah tidak ditemukan");
    }
  });

  // Menangani pemutusan koneksi klien
  ws.on("close", () => {
    console.log(" :-> Client has disconnected.");
    clients.splice(clients.indexOf(ws), 1);
  });
});

/**
 * Event listener untuk pesan Telegram
 */
bot.on("message", (msg) => {
  const chatId = msg.chat.id;
  const chatText = msg.text;
  const fromID = msg.from.id;
  const type = msg.chat.type;

  console.log(" :-> pesan masuk : " + msg.text);

  // Memeriksa apakah pengirim pesan adalah pengguna yang diizinkan
  if (fromID !== 710794517) {
    if (type != "group") {
      bot.sendMessage(chatId, "Mohon maaf ini bukan untuk umum!!!");
    } else {
      handleTelegramCommand(chatId, chatText);
    }
  } else {
    handleTelegramCommand(chatId, chatText);
  }
});

/**
 * Fungsi untuk menangani perintah Telegram
 * @param {number} chatId - ID chat Telegram
 * @param {string} command - Perintah yang diterima
 */
function handleTelegramCommand(chatId, command) {
  switch (command) {
    case "/h":
      sendCommandList(chatId);
      break;
    case "/start":
      startPM2Process("HL7Server_dev");
      bot.sendMessage(chatId, status_report);
      break;
    case "/status":
      getPM2ProcessStatus("HL7Server_dev")
        .then((status) => {
          bot.sendMessage(chatId, "Status_server : " + `${status}`);
        })
        .catch((err) => {
          bot.sendMessage(chatId, `${status}`);
        });
      break;
    case "/restart":
      restartPM2Process("HL7Server_dev");
      bot.sendMessage(chatId, status_report);
      break;
    default:
      sendCommandList(chatId);
      break;
  }
}

/**
 * Fungsi untuk mengirim daftar perintah yang tersedia ke chat Telegram
 * @param {number} chatId - ID chat Telegram
 */
function sendCommandList(chatId) {
  let response = "Daftar perintah yang tersedia:\n\n";
  commands.forEach((cmd) => {
    response += `${cmd.command} - ${cmd.description}\n`;
  });
  bot.sendMessage(chatId, response);
}

// Menangani error saat polling Telegram
bot.on("polling_error", (error) => {
  console.log(error); // Menampilkan error jika polling ada masalah
});

// Menjalankan fungsi kirimSemuaProses secara berkala sesuai dengan interval yang ditentukan
setInterval(kirimSemuaProses, monitoringInterval);

console.log(" :-> WebSocket server is running on ws://localhost:2222");
