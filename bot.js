const tele_id = ["710794517"];

const TelegramBot = require("node-telegram-bot-api");

// Token API bot Telegram dari BotFather
const token = "7084656625:AAGdIBgJI8zI9NVboAB69o4t8Ttvwdc3qQ8";

// Inisialisasi bot
const bot = new TelegramBot(token, { polling: true });
const commands = [
  { command: "/start", description: "Memulai Server HL7" },
  { command: "/stop", description: "Menghentikan Server HL7" },
  { command: "/restart", description: "Memulai ulang Server HL7" },
  { command: "/status", description: "Cek Status Server HL7" },
  { command: "/h", description: "Menampilkan bantuan" },
  // Tambahkan perintah lain sesuai kebutuhan
];

// Contoh untuk menanggapi pesan
bot.on("message", (msg) => {
  const chatId = msg.chat.id;
  const chatText = msg.text;
  const commandval = msg.entities[0].type;
  const fromID = msg.from.id;
  const type = msg.chat.type;

    console.log(msg);
  //   console.log(msg.entities[0].type);
  //   Validasi Admin

  if (fromID !== 710794517) {
    // bot.sendMessage(chatId, "Maaf ini Bukan Untuk umum!!!");

    // start validasi untuk yang diluar group
    if (type != "group") {
      bot.sendMessage(chatId, "Mohon maaf ini bukan untuk umum!!!");
    } else {
      if (commandval == "bot_command") {
        bot.sendMessage(chatId, "Mohon maaf ini bukan untuk umum!!!");
      }
    }
    // end validasi untuk yang diluar group
  } else {
    console.log(chatText);
    switch (chatText.toString()) {
      case "/h":
        // console.log('/h');
        response = "Daftar perintah yang tersedia:\n\n";
        commands.forEach((cmd) => {
          response += `${cmd.command} - ${cmd.description}\n`;
        });
        bot.sendMessage(chatId, response);
        break;
      case "/h":
        // console.log('/h');
        response = "Daftar perintah yang tersedia:\n\n";
        commands.forEach((cmd) => {
          response += `${cmd.command} - ${cmd.description}\n`;
        });
        bot.sendMessage(chatId, response);
        break;
      default:
        console.log('default');
        response = "Daftar perintah yang tersedia:\n\n";
        commands.forEach((cmd) => {
          response += `${cmd.command} - ${cmd.description}\n`;
        });
        bot.sendMessage(chatId, response);
        break;
    }

    // bot.sendMessage(
    //   chatId,
    //   "Hy " + msg.from.first_name + " ada yang bisa dibantu ?..."
    // );

    // switch (message.toString()) {
    //   case "status_HL7":
    //     // kode yang dijalankan jika nilai sama dengan nilai1
    //     ws.send(status_HL7.toString());
    //     console.log(status_HL7);
    //     break;
    //   default:
    //     ws.send("perintah tidak ditemukan");
    // }
    // console.log(msg.chat);
    // // Mengirim pesan balasan
    // bot.sendMessage(chatId, "Hallo ini Bot Pasien Monitoring");
    // bot.sendMessage(chatId, "Apa yang ingin anda perintahkan ?");
  }
});
// end validasi admin

// Mulai polling untuk mendapatkan pesan dari Telegram
bot.on("polling_error", (error) => {
  console.log(error); // Tampilkan error jika polling ada masalah
});
